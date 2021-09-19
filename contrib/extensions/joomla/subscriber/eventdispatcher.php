<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaSubscriberEventdispatcher extends ComPagesEventSubscriberAbstract
{
    public function onAfterApplicationInitialise(KEventInterface $event)
    {
        //Set the site path in the config
        if($path = $this->getObject('com:pages.config')->getSitePath('extensions'))
        {
            //Find extensions
            if($directories = glob($path.'/*', GLOB_ONLYDIR))
            {
                $listeners  = array();
                foreach ($directories as $directory)
                {
                    //The extension name
                    $name = basename($directory);

                    //Register event listeners
                    foreach (glob($directory.'/event/listener/[!_]*.php') as $filename)
                    {
                        $listener = include $filename;
                        $event    = basename($filename, '.php');

                        JEventDispatcher::getInstance()->register($event, $listener);
                    }

                    //Register event handlers
                    foreach (glob($directory.'/event/handler/[!_]*.php') as $filename)
                    {
                        $file = basename($filename, '.php');

                        if(!in_array($file, ['abstract', 'interface']))
                        {
                            include $filename;
                            $handler = 'Ext'.$name.'EventHandler'.ucfirst(basename($filename, '.php'));

                            JEventDispatcher::getInstance()->register(null, $handler);
                        }
                    }
                }
            }
        }
    }
}