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

        $this->__routers = KObjectConfig::unbox($config->routers);
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config    An optional ObjectConfig object with configuration options.
     * @return 	void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'routers' => [
                'page'     => 'com://site/pages.dispatcher.router.pages',
                'site'     => 'com://site/pages.dispatcher.router.site',
                'redirect' => 'com://site/pages.dispatcher.router.redirect',
                'url'      => 'com://site/pages.dispatcher.router.url',
            ],
        ));

        parent::_initialize($config);
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
        $identifier = null;

        //Find router identifier
        if($route instanceof KObjectInterface)
        {
            if($route instanceof ComPagesDispatcherRouterRouteInterface)
            {
                $package = $route->getScheme();

                if(isset($this->__routers[$package])) {
                    $identifier = $this->__routers[$package];
                }
            }
            else $package = $route->getIdentifier()->getPackage();
        }
        else
        {
            $package = parse_url($route, PHP_URL_SCHEME);

            if(isset($this->__routers[$package])) {
                $identifier = $this->__routers[$package];
            }
        }

        //Identifier Fallback
        if(!$identifier) {
            $identifier = 'com://site/' . $package . '.dispatcher.router.' . $package;
        }

        //Get router instance
        if(is_string($identifier))
        {
            $config = [
                'request'   => $this->getRequest(),
                'resolvers' => $this->getResolvers()
            ];

            $router =  $this->getObject($identifier, $config);

            $this->__routers[$package] = $router;
        }
        else $router = $identifier;

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
        $identifier = null;

        //Find router identifier
        if($route instanceof KObjectInterface)
        {
            if($route instanceof ComPagesDispatcherRouterRouteInterface)
            {
                $package = $route->getScheme();

                if(isset($this->__routers[$package])) {
                    $identifier = $this->__routers[$package];
                }
            }
            else $package = $route->getIdentifier()->getPackage();
        }
        else
        {
            $package = parse_url($route, PHP_URL_SCHEME);

            if(isset($this->__routers[$package])) {
                $identifier = $this->__routers[$package];
            }
        }

        //Identifier Fallback
        if(!$identifier) {
            $identifier = 'com://site/' . $package . '.dispatcher.router.' . $package;
        }

        //Get router instance
        if(is_string($identifier))
        {
            $config = [
                'request'   => $this->getRequest(),
                'resolvers' => $this->getResolvers()
            ];

            $router =  $this->getObject($identifier, $config);

            $this->__routers[$package] = $router;
        }
        else $router = $identifier;

        return $router->generate($route, $parameters);
    }
}