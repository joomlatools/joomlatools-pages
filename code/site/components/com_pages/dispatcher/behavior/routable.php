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
        return clone $this->__route;
    }

    public function getPage($content = false)
    {
        $page = false;

        if($this->__route) {
            $page = $this->getObject('page.registry')->getPage($this->__route->getPath(), $content);
        }

        return $page;
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

        if(false === $route = $context->router->resolve('pages:'.$path, $context->request->query->toArray())) {
            throw new KHttpExceptionNotFound('Page Not Found');
        }

        //Set the query in the request
        $context->request->setQuery($route->query);

        //Store the route
        $this->__route = $route;
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        //Add a (self-referential) canonical URL
        $page = $this->getPage();

        if(!$page->canonical)
        {
            $route = $context->router->generate($this->getRoute());
            $page->canonical = $context->router->qualify($route);
        }

        $this->getResponse()->getHeaders()->set('Link', array($page->canonical => array('rel' => 'canonical')));
    }
}