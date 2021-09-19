<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaEventSubscriberDispatcher extends ComPagesEventSubscriberDispatcher
{
    use ComKoowaEventTrait;

    public function onAfterKoowaBootstrap()
    {
        $this->attachEventHandler('onAfterModuleList', 'filterTemplateModules');
    }

    public function onAfterApplicationInitialise(KEventInterface $event)
    {
        $dispatcher = $this->getDispatcher();

        //Authenticate anonymous requests and inject form token dynamically
        if($dispatcher->getRequest()->isPost() && $dispatcher->isCacheable())
        {
            if($cache = $dispatcher->loadCache())
            {
                $application = JFactory::getApplication();
                if($application->input->request->get($cache['token'])) {
                    $application->input->post->set(JSession::getFormToken(), '1');
                }
            }
        }
    }

    public function onAfterApplicationRoute(KEventInterface $event)
    {
        $dispatcher = $this->getDispatcher();

        //Get the page
        if($this->isDispatchable() && !$this->isDecorator())
        {
            $page = $dispatcher->getPage();
            $menu = JFactory::getApplication()->getMenu()->getActive();

            /*
             * Configure the template
             *
             * - Set a specific template by name
             * - Set the template parameters
             */
            if($template = $page->get('process/template'))
            {
                $params = JFactory::getApplication()->getTemplate(true)->params;

                if(!is_string($template))
                {
                    if(!$template = $page->get('process/template/name')) {
                        $template = JFactory::getApplication()->getTemplate();
                    }

                    if($config = $page->get('process/template/config'))
                    {
                        foreach($config as $name => $value) {
                            $params->set($name, $value);
                        }
                    }
                }

                JFactory::getApplication()->setTemplate($template, $params);
            }
        }
        else
        {
            //Purge the cache if it exists
            if($dispatcher->isCacheable() && $dispatcher->loadCache())
            {
                $dispatcher->purge();

                //Manually set the cache status
                JFactory::getApplication()->setHeader('Cache-Status', 'DYNAMIC, PURGED');
            }
        }
    }

    public function onBeforeDispatcherDispatch(KEventInterface $event)
    {
        $config = $this->getObject('pages.config')->getOptions();

        //Configure the Joomla template
        if(isset($config['template']) || isset($config['template_config']))
        {
            if(isset($config['template'])) {
                $template = $config['template'];
            } else {
                $template = JFactory::getApplication()->getTemplate();
            }

            $params = JFactory::getApplication()->getTemplate(true)->params;
            if(isset($config['template_config']) && is_array($config['template_config']))
            {
                foreach($config['template_config'] as $name => $value) {
                    $params->set($name, $value);
                }
            }

            JFactory::getApplication()->setTemplate($template, $params);
        }
    }

    public function onAfterApplicationDispatch(KEventInterface $event)
    {
        if($this->isDispatchable())
        {
            //Set the path in the pathway to allow for module injection
            $page_route = $this->getDispatcher()->getRoute()->getPath(false);

            if($menu = JFactory::getApplication()->getMenu()->getActive()) {
                $menu_route = $menu->route;
            } else {
                $menu_route = '';
            }

            if($path = ltrim(str_replace($menu_route, '', $page_route), '/'))
            {
                $pathway = JFactory::getApplication()->getPathway();
                $router = $this->getObject('router');

                $segments = array();
                foreach(explode('/', $path) as $segment)
                {
                    $segments[] = $segment;

                    if($route = $router->generate('pages:'.implode('/', $segments)))
                    {
                        $page = $route->getPage();

                        if(!$page->name) {
                            $name = ucwords(str_replace(array('_', '-'), ' ', $page->slug));
                        } else {
                            $name = ucfirst($page->name);
                        }

                        $route = $router->qualify($route);
                        $url   = $route->toString(KHttpUrl::PATH);

                        $pathway->addItem($name, (string) $url);
                    }
                }
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
        //Cache and cleanup Joomla output if routing to a page
        if($this->isDispatchable())
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
            $dispatcher = $this->getDispatcher();
            if(!JDEBUG && $dispatcher->isCacheable() && $dispatcher->isDecorated())
            {
                $content = $event->getTarget()->getBody();

                $dispatcher->getResponse()->setHeaders($headers);
                $dispatcher->getResponse()->setContent($content);

                $dispatcher->cache();
            }
        }
    }

    public function onBeforeDispatcherCache(KEventInterface $event)
    {
        if($this->isDispatchable())
        {
            $dispatcher = $this->getDispatcher();
            $content    = $dispatcher->getResponse()->getContent();

            //Replace the session based form and csrf token with a fixed token
            $token = $dispatcher->getCacheToken();

            $search      = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
            $replacement = '<input type="hidden" name="' . $token . '" value="1" />';

            $content = preg_replace($search, $replacement, $content);

            //Search for a csrf token in the content and refresh it
            $search      = '#"csrf.token":\s*"[0-9a-f]{32}"#';
            $replacement = '"csrf.token": "' . $token . '"';

            $content = preg_replace($search, $replacement, $content);

            $dispatcher->getResponse()->setContent($content);
        }
    }

    public function filterTemplateModules(&$modules)
    {
        if($this->isDispatchable())
        {
            $page = $this->getDispatcher()->getPage();

            if($page->has('process/template/modules'))
            {
                if(count($page->get('process/template/modules')))
                {
                    foreach ($page->get('process/template/modules') as $key => $module)
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
                                unset($modules[$key]);
                            }
                        }
                        else
                        {
                            if (in_array($module->title, $exclude) || in_array($module->id, $exclude)) {
                                unset($modules[$key]);
                            }
                        }
                    }
                }
                else $modules = array();
            }
        }
    }
}