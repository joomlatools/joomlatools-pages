<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaEventSubscriberException extends ComPagesEventSubscriberAbstract
{
    public function onException(KEventInterface $event)
    {
        //Disable debugger if directly routing to a component
        if(isset($_REQUEST['option']) && substr($_REQUEST['option'], 0, 4) == 'com_') {
            $event->stopPropagation();
        }
    }
}