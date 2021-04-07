<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherHttp extends ComKoowaDispatcherHttp
{
    use ComPagesPageTrait;

    private $__router;
    private $__route;

    public function __construct( KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__router = $config->router;

        //Set the page
        $this->setPage($config->page);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors' => [
                'redirectable',
                'cacheable',
                'validatable',
                'prefetchable'
            ],
            'page'    => 'com://site/pages.page',
            'router'  => 'com://site/pages.dispatcher.router',
        ]);

        parent::_initialize($config);
    }

    public function setRouter(ComPagesDispatcherRouterInterface $router)
    {
        $this->__router = $router;
        return $this;
    }

    public function getRouter()
    {
        if(!$this->__router instanceof ComPagesDispatcherRouterInterface)
        {
            $this->__router = $this->getObject($this->__router, array(
                'request' => $this->getRequest(),
            ));

            if(!$this->__router instanceof ComPagesDispatcherRouterInterface)
            {
                throw new UnexpectedValueException(
                    'Router: '.get_class($this->__router).' does not implement ComPagesDispatcherRouterInterface'
                );
            }
        }

        return $this->__router;
    }

    public function getRoute()
    {
        $result = false;

        if(!isset($this->__route) && $this->getObject('pages.config')->getSitePath() !== false)
        {
            $base  = $this->getRequest()->getBasePath();
            $url   = urldecode($this->getRequest()->getUrl()->getPath());

            //Strip script name if request is rewritten
            if(!isset($_SERVER['PAGES_PATH'])) {
                $path = str_replace(array($base, basename($_SERVER['SCRIPT_NAME'])), '', $url);
            } else {
                $path = str_replace($base, '', $url);
            }

            $path  = trim($path, '/');
            $query = $this->getRequest()->getUrl()->getQuery(true);

            if($route = $this->getRouter()->resolve('pages:'.$path,  $query)) {
                $this->getPage()->setProperties($route->getPage());
            }

            $this->__route = $route;
        }

        if(is_object($this->__route)) {
            $result = clone $this->__route;
        }

        return $result;
    }

    public function getHttpMethods()
    {
        $methods =  array('head', 'options');

        if($page = $this->getPage())
        {
            if($page->isSubmittable())
            {
                //Do not allow get on empty forms or collection, only used as API endpoints
                if($page->getContent() || $page->layout) {
                    $methods[] = 'get';
                }
            }
            else $methods[] = 'get';

            if($page->isSubmittable()) {
                $methods[] = 'post';
            }

            if($page->isEditable())
            {
                $methods[] = 'post';
                $methods[] = 'put';
                $methods[] = 'patch';
                $methods[] = 'delete';
            }
        }

        return $methods;
    }

    public function getHttpFormats()
    {
        $formats = array();

        if($page = $this->getPage())
        {
            $formats = (array) $page->format;

            if($collection = $page->isCollection())
            {
                if(isset($collection['format'])) {
                    $formats = array_merge($formats, (array) $collection['format']);
                }
            }
        }

        return array_unique($formats);
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        //Throw 404 if the site was not found
        if(false ===  $this->getObject('pages.config')->getSitePath()) {
            throw new KHttpExceptionNotFound('Site Not Found');
        }

        //Throw 404 if the page was not found
        if(false === $route = $this->getRoute()) {
            throw new KHttpExceptionNotFound('Page Not Found');
        }

        //Set the query in the request
        $context->request->setQuery($route->query);

        //Throw 415 if the media type is not allowed
        $format = strtolower($context->request->getFormat());
        if (!in_array($format, $this->getHttpFormats()))
        {
            $accept = $context->request->getAccept();

            //Use default if no accept header or accept includes */*
            if(empty($accept) || array_key_exists('*/*', $accept)) {
                $context->request->setFormat($context->page->format);
            } else {
                throw new KHttpExceptionNotAcceptable('Format not supported');
            }
        }
    }

    protected function _actionDispatch(KDispatcherContextInterface $context)
    {
        //Set the controller
        $this->setController($context->page->getType(), ['page' =>  $context->page]);

        //Throw 405 if the method is not allowed
        $method = strtolower($context->request->getMethod());
        if (!in_array($method, $this->getHttpMethods())) {
            throw new KDispatcherExceptionMethodNotAllowed('Method not allowed');
        }

        //Execute the component method
        $this->execute($method, $context);

        KDispatcherAbstract::_actionDispatch($context);
    }

    protected function _actionGet(KDispatcherContextInterface $context)
    {
        if($collection =  $context->page->isCollection())
        {
            if(isset($collection['state']) && isset($collection['state']['limit']))
            {
                $this->getConfig()->limit->default  = $collection['state']['limit'];
                $this->getConfig()->limit->max      = $collection['state']['limit'];
            }
        }

        return parent::_actionGet($context);
    }

    protected function _actionPost(KDispatcherContextInterface $context)
    {
        if(!$context->page->isForm())
        {
            if(!$context->request->data->has('_action'))
            {
                $action = $this->getController()->getModel()->isAtomic() ? 'edit' : 'add';
                $context->request->data->set('_action', $action);
            }

            $result = parent::_actionPost($context);

        }
        else $result = $this->getController()->execute('submit', $context);

        return $result;
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        //Add a (self-referential) canonical URL (only to GET and HEAD requests)
        if($context->page && $context->request->isCacheable())
        {
            if(!$context->page->canonical && $this->getRoute())
            {
                $route = $context->router->generate($this->getRoute());
                $context->page->canonical = (string) $context->router->qualify($route);
            }

            $this->getResponse()->getHeaders()->set('Link', array($context->page->canonical => array('rel' => 'canonical')));

            //Add X-Robots-Tag
            if($context->page->metadata->has('robots'))
            {
                $tags = KObjectConfig::unbox($context->page->metadata->robots);
                $this->getResponse()->getHeaders()->set('X-Robots-Tag', $tags);
            }
        }
    }

    protected function _actionSend(KDispatcherContextInterface $context)
    {
        //Do not send the response if it was already send
        if(!headers_sent()) {
            parent::_actionSend($context);
        } else {
            $this->terminate($context);
        }
    }

    public function getContext()
    {
        $context = new ComPagesDispatcherContext();
        $context->setSubject($this);
        $context->setRequest($this->getRequest());
        $context->setResponse($this->getResponse());
        $context->setUser($this->getUser());
        $context->setRouter($this->getRouter());
        $context->setPage($this->getPage());

        return $context;
    }
}