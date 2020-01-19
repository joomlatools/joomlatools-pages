<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberDispatcher extends ComPagesEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function onAfterApplicationRoute(KEventInterface $event)
    {
        $site_path  =  $this->getObject('com://site/pages.config')->getSitePath();
        $page_route = $route = $this->getObject('com://site/pages.dispatcher.http')->getRoute();

        if($page_route && $site_path)
        {
            $page = trim($page_route->getPath(false), '/');

            $decorate = $this->getObject('page.registry')
                ->getPage($page)
                ->process->get('decorate', false);

            if($decorate === false) {
                $event->getTarget()->input->set('option', 'com_pages');
            }
        }
    }
}