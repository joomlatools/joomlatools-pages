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
        $option = $this->getObject('request')->query->get('option', 'cmd');
        $route  = $this->getObject('com:pages.router')->route();

        if($route['page'] && $option != 'com_pages' )
        {
            $buffer = $event->getTarget()->getDocument()->getBuffer('component');

            ob_start();

            $dispatcher = $this->getObject('com://site/pages.dispatcher.http');
            $dispatcher->getResponse()->setContent($buffer);
            $dispatcher->dispatch();

            $result = ob_get_clean();

            $event->getTarget()->getDocument()->setBuffer($result, 'component');
        }
    }
}