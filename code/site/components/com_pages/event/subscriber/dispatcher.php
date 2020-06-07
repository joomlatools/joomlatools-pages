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
        $site_path  = $this->getObject('com://site/pages.config')->getSitePath();
        $page_route = $route = $this->getObject('com://site/pages.dispatcher.http')->getRoute();

        if($page_route !== false && $site_path !== false)
        {
            $page    = $page_route->getPage();
            $request = $this->getObject('request');

            if($request->isSafe())
            {
                /**
                 * Route safe requests to pages under the following conditions:
                 *
                 * 	- Joomla fell back to the default menu item because the page route couldn't be resolved
                 *  - The Joomla menu item isn't being decorated
                 */

                if(JFactory::getApplication()->getMenu()->getActive()->home && !empty($page->path)) {
                    $event->getTarget()->input->set('option', 'com_pages');
                } elseif($page->process->get('decorate', false) === false) {
                    $event->getTarget()->input->set('option', 'com_pages');
                }
            }
            else
            {
                /**
                 * Route none-safe requests to pages under the following conditions:
                 *
                 * 	- Joomla fell back to the default menu item because the page route couldn't be resolved
                 */
                if(JFactory::getApplication()->getMenu()->getActive()->home && !empty($page->path)) {
                    $event->getTarget()->input->set('option', 'com_pages');
                }
            }

            /*
             * Configure the template
             *
             * - Set a specific template by name
             * - Set the template parameters
             */
            if($page->process->has('template'))
            {
                if($page->process->template->has('name')) {
                    $template = $page->process->template->name;
                } else {
                    $template = JFactory::getApplication()->getTemplate();
                }

                $params = JFactory::getApplication()->getTemplate(true)->params;

                if($page->process->template->has('config'))
                {
                    foreach($page->process->template->config as $name => $value) {
                        $params->set($name, $value);
                    }
                }

                JFactory::getApplication()->setTemplate($template, $params);
            }
        }
    }

    public function onAfterTemplateModules(KEventInterface $event)
    {
        if($this->getObject('com://site/pages.dispatcher.http')->getRoute())
        {
            $page = $this->getObject('com://site/pages.dispatcher.http')->getRoute()->getPage();

            if($page->process->has('template') && $page->process->template->has('modules'))
            {
                $modules = $page->process->template->modules;

                if(count($modules))
                {
                    foreach ($event->modules as $key => $module)
                    {
                        $include = array();
                        $exclude = array();

                        foreach ($page->process->template->get('modules') as $id)
                        {
                            if ($id[0] == '-' || $id < 0) {
                                $exclude[] = substr($id, 1);
                            } else {
                                $include[] = $id;
                            }
                        }

                        if ($include)
                        {
                            if (!in_array($module->title, $include) && !in_array($module->id, $include)) {
                                unset($event->modules[$key]);
                            }
                        }
                        else
                        {
                            if (in_array($module->title, $exclude) || in_array($module->id, $exclude)) {
                                unset($event->modules[$key]);
                            }
                        }
                    }
                }
                else $event->modules  = array();
            }
        }
    }
}
