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
    public function onException(KEventException $event)
    {
        if($this->getObject('request')->getFormat() == 'html')
        {
            //Let pages handle errors even if pages is not the active component. This allows for a site wide
            //implementation of error pages through pages.
            if($this->getObject('com://site/pages.dispatcher.http')->fail($event)) {
                return true;
            }
        }

        return false;
    }
}