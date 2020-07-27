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

    public function onAfterApplicationInitialise(KEventInterface $event)
    {
        $dispatcher = $this->getObject('com://site/pages.dispatcher.http');
        $application = JFactory::getApplication();

        //Turn off sh404sef for com_pages
        if(JComponentHelper::isEnabled('com_sh404sef'))
        {
            //Tun route parsing
            $application->getRouter()->attachParseRule(function($router, $url)
            {
                if(class_exists('Sh404sefClassRouterInternal'))
                {
                    $page = $dispatcher->getPage();

                    if($page !== false && !$page->isDecorator()) {
                        Sh404sefClassRouterInternal::$parsedWithJoomlaRouter = true;
                    }
                }

            },  JRouter::PROCESS_BEFORE);

            //Tun off route building
            $application->getRouter()->attachBuildRule(function($router, $url)
            {
                if(class_exists('Sh404sefFactory')) {
                    Sh404sefFactory::getConfig()->useJoomlaRouter[] = 'pages';
                }

            },  JRouter::PROCESS_BEFORE);
        }

        //Authenticate anonymous requests and inject form token dynamically
        if($dispatcher->getRequest()->isPost())
        {
            if($cache = $dispatcher->loadCache())
            {
                if($application->input->request->get($cache['token'])) {
                    $application->input->post->set(JSession::getFormToken(), '1');
                }
            }
        }
    }

    public function onAfterApplicationRoute(KEventInterface $event)
    {
        $dispatcher = $this->getObject('com://site/pages.dispatcher.http');

        //Get the page
        if($page = $dispatcher->getPage())
        {
            if($this->getObject('request')->isSafe())
            {
                /**
                 * Route safe requests to pages under the following conditions:
                 *
                 * 	- Joomla fell back to the default menu item because the page route couldn't be resolved
                 *  - The Joomla menu item isn't being decorated
                 */

                if(JFactory::getApplication()->getMenu()->getActive()->home && !empty($page->path)) {
                    $event->getTarget()->input->set('option', 'com_pages');
                } elseif(!$page->isDecorator()) {
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

    public function onAfterApplicationRender(KEventInterface $event)
    {
        if(!headers_sent())
        {
            header_register_callback( function() {
                header_remove('Expires');
            });
        }
    }

    public function onAfterApplicationRespond(KEventInterface $event)
    {
        $dispatcher = $this->getObject('com://site/pages.dispatcher.http');

        //Cache and cleanup Joomla output if routing to a page
        if($route = $dispatcher->getRoute())
        {
            $headers = array();
            foreach (headers_list() as $header)
            {
                $parts = explode(':', $header, 2);
                $headers[trim($parts[0])] = trim($parts[1]);
            }

            //Remove the Expires header
            unset($headers['Expires']);

            //Do not cache if Joomla is running in debug mode
            if(!JDEBUG && $dispatcher->isCacheable() && $dispatcher->isDecorated())
            {
                $content = $event->getTarget()->getBody();

                //Replace the session based form and csrf token with a fixed token
                $token = $dispatcher->getCacheToken();

                $search      = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
                $replacement = '<input type="hidden" name="' . $token . '" value="1" />';

                $content = preg_replace($search, $replacement, $content);

                //Search for a csrf token in the content and refresh it
                $search      = '#"csrf.token": "[0-9a-f]{32}"#';
                $replacement = '"csrf.token": "' . $token . '"';

                $content = preg_replace($search, $replacement, $content);

                $dispatcher->getResponse()->setHeaders($headers);
                $dispatcher->getResponse()->setContent($content);

                $dispatcher->cache();
            }
        }
    }

    public function onAfterTemplateModules(KEventInterface $event)
    {
        $dispatcher = $this->getObject('com://site/pages.dispatcher.http');

        if($page = $dispatcher->getPage())
        {
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