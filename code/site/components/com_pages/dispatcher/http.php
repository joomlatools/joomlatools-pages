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
    private $__router;

    public function __construct( KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__router = $config->router;

        //Re-register the exception event listener to run through pages scope
        $this->addEventListener('onException', array($this, 'fail'));
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([

            'behaviors' => [
                'configurable',
                'redirectable',
                'routable',
                //'cacheable',  Injected by ComPagesDispatcherRouterResolverSite
            ],
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

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        //Do not call parent
    }

    protected function _actionDispatch(KDispatcherContextInterface $context)
    {
        //Throw 405 if the method is not allowed
        $method = strtolower($context->request->getMethod());
        if (!in_array($method, $this->getHttpMethods())) {
            throw new KDispatcherExceptionMethodNotAllowed('Method not allowed');
        }

        //Get the page from the router
        $page = $this->getRoute()->getPage();

        //Set the controller
        $this->setController($page->getType(), ['model' => $page]);

        //Execute the component method
        $this->execute($method, $context);

        KDispatcherAbstract::_actionDispatch($context);
    }

    protected function _actionGet(KDispatcherContextInterface $context)
    {
        //Use hardcoded limit if page has one
        $page = $this->getRoute()->getPage();

        if($collection = $page->isCollection())
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
        if($this->getRoute()->getPage()->isForm()) {
            $result = $this->getController()->execute('submit', $context);
        } else {
            $result = parent::_actionPost($context);
        }

        return $result;
    }

    protected function _renderError(KDispatcherContextInterface $context)
    {
        if(!JDEBUG && $this->getObject('request')->getFormat() == 'html')
        {
            //Get the exception object
            if($context->param instanceof KEventException) {
                $exception = $context->param->getException();
            } else {
                $exception = $context->param;
            }

            foreach([(int) $exception->getCode(), '500'] as $code)
            {
                if($page = $this->getObject('page.registry')->getPage($code))
                {
                    //Set the controller
                    $this->setController($page->getType(), ['model' => $page]);

                    //Render the error
                    $content = $this->getController()->render($exception);

                    //Set error in the response
                    $context->response->setContent($content);

                    //Set status code
                    $context->response->setStatus($exception->getCode());

                    return true;
                }
            }
        }

        return parent::_renderError($context);
    }

    public function getContext()
    {
        $context = new ComPagesDispatcherContext();
        $context->setSubject($this);
        $context->setRequest($this->getRequest());
        $context->setResponse($this->getResponse());
        $context->setUser($this->getUser());
        $context->setRouter($this->getRouter());

        return $context;
    }

    public function getHttpMethods()
    {
        $page = $this->getRoute()->getPage(true);

        if($page->isForm())
        {
            if($page->layout || !empty($page->getContent())) {
                $methods =  array('get', 'head', 'options', 'post');
            } else {
                $methods =  array('post');
            }
        }
        else $methods =  array('get', 'head', 'options');

        return $methods;
    }
}