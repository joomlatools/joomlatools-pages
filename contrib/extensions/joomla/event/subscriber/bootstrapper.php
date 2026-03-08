<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaEventSubscriberBootstrapper extends ComPagesEventSubscriberAbstract
{
    public function onAfterPagesBootstrap(KEventInterface $event)
    {
        if(class_exists('\Joomla\CMS\Component\ComponentHelper'))
        {
            //Add com_pages to Joomla components
            $install = Closure::bind(function()
            {
                static::$components['com_pages'] = new Joomla\CMS\Component\ComponentRecord([
                    'option'  => 'com_pages',
                    'enabled' => 1
                ]);

            }, null, '\Joomla\CMS\Component\ComponentHelper');

            $install();
        }

        //Set script_name based on Joomla's sef_rewrite setting
        $sefRewrite = (bool) JFactory::getApplication()->getCfg('sef_rewrite');
        $event->config->set('script_name', $sefRewrite ? '' : 'index.php');
    }
}