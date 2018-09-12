<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberDecorator extends KEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function onAfterApplicationDispatch(KEventInterface $event)
    {
        $menu = JFactory::getApplication()->getMenu()->getActive();

        if($menu->component !== 'com_pages')
        {
            $page_route = $this->getObject('com:pages.router')->getRoute();

            $base  = trim(dirname($menu->route), '.');
            $route = trim(str_replace($base, '', $page_route), '/');

            $page = $base ? $base.'/'.$route : $route;

            while($page && !$this->getObject('page.registry')->isPage($page))
            {
                if($route = trim(dirname($route), '.')) {
                    $page  = $base ? $base.'/'.$route : $route;
                } else {
                    $page = false;
                }
            }

            if($page)
            {
                $buffer = $event->getTarget()->getDocument()->getBuffer('component');

                ob_start();

                $dispatcher = $this->getObject('com://site/pages.dispatcher.http');

                $dispatcher->getRequest()->getUrl()->setPath($page);
                $dispatcher->getResponse()->setContent($buffer);
                $dispatcher->dispatch();

                $result = ob_get_clean();

                $event->getTarget()->getDocument()->setBuffer($result, 'component');
            }
        }
    }
}