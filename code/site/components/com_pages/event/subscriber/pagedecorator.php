<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberPagedecorator extends ComPagesEventSubscriberAbstract
{
    protected $_dispatcher;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function onAfterApplicationRoute(KEventInterface $event)
    {
        //Try to validate the cache
        if($dispatcher = $this->getDispatcher())
        {
            if($dispatcher->isCacheable()) {
                $dispatcher->validate();
            }
        }
    }

    public function onAfterApplicationDispatch(KEventInterface $event)
    {
        if($dispatcher = $this->getDispatcher())
        {
            $buffer = JFactory::getDocument()->getBuffer('component');

            ob_start();

            $dispatcher->getResponse()->setContent($buffer);
            $dispatcher->dispatch();

            $result = ob_get_clean();

            JFactory::getDocument()->setBuffer($result, 'component');
        }
    }

    public function getDispatcher()
    {
        $menu = JFactory::getApplication()->getMenu()->getActive();

        $component  = $menu ? $menu->component : '';
        $menu_route = $menu ? $menu->route : '';

        //Only decorate GET requests that are not routing to com_pages
        if(is_null($this->_dispatcher) && $this->getObject('request')->isGet() && $component != 'com_pages')
        {
            $page_route = $route = $this->getObject('com://site/pages.dispatcher.http')->getRoute();

            if($page_route)
            {
                $this->_dispatcher = false;
                $page_route = $page_route->getPath(false);

                $base  = trim(dirname($menu_route), '.');
                $route = trim(str_replace($base, '', $page_route), '/');

                $page = $base ? $base.'/'.$route : $route;

                $level = 0;
                while($page && !$this->getObject('page.registry')->isPage($page))
                {
                    if($route = trim(dirname($route), '.'))
                    {
                        $page = $base ? $base.'/'.$route : $route;
                        $level++;
                    }
                    else $page = false;
                }

                if($page !== false)
                {
                    $decorate = $this->getObject('page.registry')
                        ->getPage($page)
                        ->process->get('decorate', false);

                    if($decorate === true || (is_int($decorate) && ($decorate >= $level)))
                    {
                        $dispatcher = $this->getObject('com://site/pages.dispatcher.http', ['controller' => 'decorator']);

                        $dispatcher->getResponse()->getHeaders()->set('Content-Location',  clone $dispatcher->getRequest()->getUrl());
                        $dispatcher->getRequest()->getUrl()->setPath('/'.$page);

                        $this->_dispatcher = $dispatcher;
                    }
                }
            }
        }

        return $this->_dispatcher;
    }
}