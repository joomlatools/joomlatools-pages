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
            $page_route = $this->getObject('com:pages.dispatcher.router.route')->getRoute();

            $base  = trim(dirname($menu->route), '.');
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

            if($page)
            {
                $decorate = $this->getObject('page.registry')
                    ->getPage($page)
                    ->process->get('decorate', true);

                if($decorate === true || (is_int($decorate) && ($decorate >= $level))) {
                    $this->_decoratePage($page, $event->getTarget());
                }
            }
        }
    }

    protected function _decoratePage($page, $app)
    {
        $buffer = $app->getDocument()->getBuffer('component');

        ob_start();

        $dispatcher = $this->getObject('com://site/pages.dispatcher.http');

        $dispatcher->getRequest()->getUrl()->setPath($page);
        $dispatcher->getResponse()->setContent($buffer);
        $dispatcher->dispatch();

        $result = ob_get_clean();

        $app->getDocument()->setBuffer($result, 'component');
    }
}