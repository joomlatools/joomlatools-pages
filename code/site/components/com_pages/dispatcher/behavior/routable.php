<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorRoutable extends KControllerBehaviorAbstract
{
    private $__route;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_NORMAL,
        ));

        parent::_initialize($config);
    }

    public function getRoute()
    {
        if(!isset($this->__route))
        {
            $request = $this->getRequest();

            $base   = $request->getBasePath();
            $format = $request->getFormat();

            $url  = urldecode($request->getUrl()->getPath());
            $path = trim(str_replace(array($base, '/index.php'), '', $url), '/');

            //Resolve the path
            $route = $this->getRouter()->resolve('pages:'.$path, $request->query->toArray());

            //Clone the route
            if(is_object($route)) {
                $this->__route = clone $route;
            } else {
                $this->__route = $route;
            }
        }

        return $this->__route;
    }


    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        if(false === $route = $this->getRoute()) {
            throw new KHttpExceptionNotFound('Page Not Found');
        }

        //Set the query in the request
        $context->request->setQuery($route->query);
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        //Add a (self-referential) canonical URL (only to GET and HEAD requests)
        if($context->page && $context->request->isCacheable())
        {
            if(!$context->page->canonical)
            {
                $route = $context->router->generate($this->getRoute());
                $context->page->canonical = (string) $context->router->qualify($route);
            }

            $this->getResponse()->getHeaders()->set('Link', array($context->page->canonical => array('rel' => 'canonical')));
        }
    }
}