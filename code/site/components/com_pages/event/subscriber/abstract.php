<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesEventSubscriberAbstract extends KEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'enable'  => true,
        ));

        parent::_initialize($config);
    }

    public function subscribe(KEventPublisherInterface $publisher)
    {
        $result = [];

        if($this->isEnabled()) {
            $result = parent::subscribe($publisher);
        }

        return $result;
    }

    public function unsubscribe(KEventPublisherInterface $publisher)
    {
        if($this->isEnabled()) {
            parent::unsubscribe($publisher);
        }
    }

    public function isEnabled()
    {
        return $this->getConfig()->enable;
    }
}

