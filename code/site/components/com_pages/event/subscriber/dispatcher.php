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
    use ComKoowaEventTrait;

    private $__dispatchable;

    /**
     * Constructor.
     *
     * @param KObjectConfig $config  An optional ObjectConfig object with configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Disable dispatching if directly routing to a component
        if(isset($_REQUEST['option']) && substr($_REQUEST['option'], 0, 4) == 'com_') {
            $this->__dispatchable = false;
        } else {
            $this->__dispatchable = true;
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function onAfterApplicationInitialise(KEventInterface $event)
    {
        $dispatcher = $this->getDispatcher();

        if($this->isDispatchable() && !$this->isDecorator())
        {
            $application = JFactory::getApplication();

            //Turn off component sef_advanced to bypass routing failure
            //See: https://github.com/joomla/joomla-cms/blob/staging/libraries/src/Router/Router.php#L238
            $application->getRouter()->attachParseRule(function($router, $url) use ($dispatcher)
            {
                if($dispatcher->getRoute())
                {
                    $page = $dispatcher->getPage();

                    if($page->path && !$page->isDecorator())
                    {
                        $option = $router->getVar('option');
                        JComponentHelper::getParams($option)->set('sef_advanced', 0);
                    }
                }

            },  JRouter::PROCESS_AFTER);


            //Turn off sh404sef for com_pages
            if(JComponentHelper::isEnabled('com_sh404sef'))
            {
                //Tun route parsing
                $application->getRouter()->attachParseRule(function($router, $url) use ($dispatcher)
                {
                    if(class_exists('Sh404sefClassRouterInternal'))
                    {
                        if($dispatcher->getRoute())
                        {
                            $page = $dispatcher->getPage();

                            if($page->path && !$page->isDecorator()) {
                                Sh404sefClassRouterInternal::$parsedWithJoomlaRouter = true;
                            }
                        }
                    }

                },  JRouter::PROCESS_BEFORE);

                //Tun off route building
                $application->getRouter()->attachBuildRule(function($router, $url) use ($dispatcher)
                {
                    if(class_exists('Sh404sefFactory')) {
                        Sh404sefFactory::getConfig()->useJoomlaRouter[] = 'pages';
                    }

                },  JRouter::PROCESS_BEFORE);
            }
        }
    }

    public function onAfterApplicationRoute(KEventInterface $event)
    {
        $dispatcher = $this->getDispatcher();

        //Get the page
        if($this->isDispatchable() && !$this->isDecorator())
        {
            $dispatch = false;

            $page = $dispatcher->getPage();
            $menu = JFactory::getApplication()->getMenu()->getActive();

            if($this->getObject('request')->isSafe())
            {
                /**
                 * Route safe requests to pages under the following conditions:
                 *
                 * 	- Joomla fell back to the default menu item because the page route couldn't be resolved
                 *  - The Joomla menu item isn't being decorated
                 */

                if($menu && $menu->home && !empty($page->path)) {
                    $dispatch = true;
                } elseif(!$page->isDecorator()) {
                    $dispatch = true;
                }
            }
            else
            {
                /**
                 * Route none-safe requests to pages under the following conditions:
                 *
                 * 	- Page is a form
                 *  - Joomla fell back to the default menu item because the page route couldn't be resolved
                 */
                if($page->isForm() || ($menu && $menu->home && !empty($page->path))) {
                    $dispatch = true;
                }
            }

            if($dispatch)
            {
                $event->getTarget()->input->set('option', 'com_pages');

                $path = JPATH_LIBRARIES.'/joomlatools-components/pages';

                define('JPATH_COMPONENT', $path);
                define('JPATH_COMPONENT_SITE', $path);
                define('JPATH_COMPONENT_ADMINISTRATOR', $path);
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

    public function getDispatcher()
    {
        return $this->getObject('com:pages.dispatcher.http');
    }

    public function isDispatchable()
    {
        return (bool) $this->__dispatchable && $this->getDispatcher()->getRoute();
    }

    public function isDecorator()
    {
        return (bool) $this->isDispatchable() && $this->getDispatcher()->getPage()->isDecorator();
    }
}