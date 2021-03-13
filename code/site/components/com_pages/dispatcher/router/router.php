<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Dispatcher Router Singleton
 *
 * Force the router object to a singleton with identifier alias 'router'.
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router
 */
class ComPagesDispatcherRouter extends ComPagesDispatcherRouterAbstract implements KObjectSingleton
{
    /**
     * The routers
     *
     * @var	array
     */
    private $__routers;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Add a global object alias
        $this->getObject('manager')->registerAlias($this->getIdentifier(), 'router');
    }

    /**
     * Resolve a route
     *
     * Proxy to the specific component router
     *
     * @param string|ComPagesDispatcherRouterRouteInterface|KObjectInterface $route The route to resolve
     * @param array $parameters Route parameters
     * @return false| ComPagesDispatcherRouterInterface Returns the matched route or false if no match was found
     */
    public function resolve($route, array $parameters = array())
    {
        $result = false;

        //Find router package
        if($route instanceof KObjectInterface)
        {
            if($route instanceof ComPagesDispatcherRouterRouteInterface) {
                $package = $route->getScheme();
            } else {
                $package = $route->getIdentifier()->getPackage();
            }
        }
        else
        {
            $parts   = explode(':', $route);
            $package = $parts[0];
        }

        //Get router instance
        if(!isset($this->__routers[$package]))
        {
            $identifier = 'com://site/'.$package.'.dispatcher.router.'.$package;

            $config = [
                'request'   => $this->getRequest(),
                'resolvers' => $this->getResolvers()
            ];

            $router =  $this->getObject($identifier, $config);

            $this->__routers[$package] = $router;
        }
        else $router = $this->__routers[$package];

        return $router->resolve($route, $parameters);
    }

    /**
     * Generate a route
     *
     *  Proxy to the specific component router
     *
     * @param string|ComPagesDispatcherRouterRouteInterface|KObjectInterface $route The route to resolve
     * @param array $parameters Route parameters
     * @return false|KHttpUrlInterface Returns the generated route
     */
    public function generate($route, array $parameters = array())
    {
        $result = false;

        //Find router package
        if($route instanceof KObjectInterface)
        {
            if($route instanceof ComPagesDispatcherRouterRouteInterface) {
                $package = $route->getScheme();
            } else {
                $package = $route->getIdentifier()->getPackage();
            }
        }
        else $package = parse_url($route, PHP_URL_SCHEME);

        //Get router instance
        if(!isset($this->__routers[$package]))
        {
            $identifier = 'com://site/'.$package.'.dispatcher.router.'.$package;

            $config = [
                'request'   => $this->getRequest(),
                'resolvers' => $this->getResolvers()
            ];

            $router =  $this->getObject($identifier, $config);

            $this->__routers[$package] = $router;
        }
        else $router = $this->__routers[$package];

        return $router->generate($route, $parameters);
    }
}