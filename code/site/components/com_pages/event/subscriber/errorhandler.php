<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberErrorhandler extends KEventSubscriberAbstract
{
    public function __construct( KObjectConfig $config)
    {
        parent::__construct($config);

        //Exception Handling
        $this->getObject('event.publisher')->addListener('onException', array($this, 'onException'));
    }

    public function onException(KEventException $event)
    {
        if(!JDEBUG && $this->getObject('request')->getFormat() == 'html')
        {
            if($this->getObject('com://site/pages.dispatcher.http')->fail($event)) {
                return true;
            }
        }

        return false;
    }
}

