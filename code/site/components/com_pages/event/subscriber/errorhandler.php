<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberErrorhandler extends ComPagesEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'debug' => JDEBUG,
        ));
    }

    public function __construct( KObjectConfig $config)
    {
        parent::__construct($config);

        //Exception Handling
        $this->getObject('event.publisher')->addListener('onException', array($this, 'onException'));
    }

    public function onException(KEventException $event)
    {
        if(!$this->isDebug() && $this->getObject('request')->getFormat() == 'html')
        {
            if($this->getObject('com://site/pages.dispatcher.http')->fail($event)) {
                return true;
            }
        }

        return false;
    }

    public function isDebug()
    {
        return $this->getConfig()->debug;
    }
}

