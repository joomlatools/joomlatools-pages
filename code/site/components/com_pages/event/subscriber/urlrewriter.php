<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberUrlrewriter extends ComPagesEventSubscriberAbstract
{
    private $__routes  = array();

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority'   => KEvent::PRIORITY_HIGH,
            'cache_path' => $this->getObject('com://site/pages.config')->getSitePath('cache'),
        ));

        parent::_initialize($config);
    }

    public function onAfterApplicationInitialise(KEventInterface $event)
    {
        //Load the routes
        $path = $this->getConfig()->cache_path;
        $file = $path.'/rewrites.php';

        if(file_exists($file)) {
            $this->__routes = require($file);
        }

        //Attach build rule
        JFactory::getApplication()->getRouter()->attachBuildRule(function($router, $url)
        {
            $path  = trim(str_replace(array('index.php'), '', $url->getPath()), '/');
            $query = $url->getQuery(true);

            $request = $this->getObject('request');
            $router  = $this->getObject('com://site/pages.dispatcher.router.url', ['request' => $request]);

            //Internal rewrite from old to new url
            if($route = $router->resolve($path, $query))
            {
                $target = trim($route->getPath(), '/');

                if(strpos($router->getRequest()->getUrl()->getPath(), 'index.php') !== false) {
                    $url->setPath('index.php/' . $target);
                } else {
                    $url->setPath($target);
                }

                $url->setQuery($route->getQuery(true));

                //Cache the route resolution
                if($path != $target) {
                    $this->__routes[$path] = $target;
                }
            }

        },  JRouter::PROCESS_AFTER);

        //Attach parse rule
        JFactory::getApplication()->getRouter()->attachParseRule(function($router, $url)
        {
            $path  = trim(str_replace(array('index.php'), '', $url->getPath()), '/');
            $query = $url->getQuery(true);

            $request = $this->getObject('request');
            $router  = $this->getObject('com://site/pages.dispatcher.router.url', ['request' => $request]);

            if(isset($this->__routes[$path]))
            {
                //Redirect OLD to NEW
                if($route = $router->resolve($path, $query))
                {
                    $route = $router->getRoute(trim($route->getPath(), '/'));
                    $url   = $router->qualify($route);

                    $this->getObject('com://site/pages.dispatcher.http')->redirect($url);
                }
            }

            if($old = array_search($path, $this->__routes))
            {
                //Redirect NEW to OLD
                if(!$router->resolve($old, $query))
                {
                    $route = $router->getRoute($old);
                    $url   = $router->qualify($route);

                    $this->getObject('com://site/pages.dispatcher.http')->redirect($url);
                }
                else $url->setPath($old);
            }

        },  JRouter::PROCESS_BEFORE);
    }

    public function onBeforeApplicationTerminate(KEventInterface $event)
    {
        //Store the routes
        $path = $this->getConfig()->cache_path;
        $file = $path.'/rewrites.php';

        $result = '<?php /*//path:'.$file.'*/'."\n";
        $result .= 'return '.var_export($this->__routes, true).';';

        if(@file_put_contents($file, $result) === false) {
            throw new RuntimeException(sprintf('The routes cannot be cached in "%s"', $file));
        }
    }
}