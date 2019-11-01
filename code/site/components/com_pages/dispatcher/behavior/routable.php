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
        $result = null;

        if(is_object($this->__route)) {
            $result = clone $this->__route;
        }

        return $result;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        $base   = $context->request->getBasePath();
        $format = $context->request->getFormat();

        $url  = urldecode($context->request->getUrl()->getPath());
        $path = trim(str_replace(array($base, '/index.php'), '', $url), '/');

        //Append the format
        if($format !== 'html' && strpos($path,  '.'.$format) == false ) {
            $path .= '.'.$format;
        }

        if(false !== $route = $context->router->resolve('pages:'.$path, $context->request->query->toArray()))
        {
            //Set the query in the request
            $context->request->setQuery($route->query);

            //Set the page in the context
            $context->page = $route->getPage();
        }

        //Store the route
        $this->__route = $route;
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        //Add a (self-referential) canonical URL
        if($page = $context->page)
        {
            if(!$page->canonical)
            {
                $route = $context->router->generate($this->getRoute());
                $page->canonical = (string) $context->router->qualify($route);
            }

            $this->getResponse()->getHeaders()->set('Link', array($page->canonical => array('rel' => 'canonical')));
        }
    }
}