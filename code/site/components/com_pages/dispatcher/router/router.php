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
     * List of router resolvers
     *
     * @var array
     */
    private $__resolvers;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Add a global object alias
        $this->getObject('manager')->registerAlias($this->getIdentifier(), 'router');
    }

    /**
     * Compile a route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface $route The route to compile
     * @param array $parameters Route parameters
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function compile($route, array $parameters = array())
    {
        //Do not try to compile the route
        return $route;
    }

    /**
     * Get the resolver
     *
     * @param string|ComPagesDispatcherRouterRouteInterface|KObjectInterface $route The route to resolve
     * @return false|ComPagesDispatcherRouterInterface
     */
    public function getResolver($route)
    {
        $resolver = false;

        if($route instanceof KObjectInterface)
        {
            if($route instanceof ComPagesDispatcherRouterRouteInterface) {
                $component = $route->getScheme();
            } else {
                $component = $route->getIdentifier()->getPackage();
            }
        }
        else $component = parse_url($route, PHP_URL_SCHEME);

        if($component)
        {
            if (!isset($this->__resolvers[$component]))
            {
                $config     = ['request' => $this->getRequest()];
                $identifier = 'com://site/'.$component.'.dispatcher.router.'.$component;

                $resolver = $this->getObject($identifier, $config);

                if (!($resolver instanceof ComPagesDispatcherRouterInterface))
                {
                    throw new UnexpectedValueException(
                        "Resolver $identifier does not implement DispatcherRouterInterface"
                    );
                }

                $this->__resolvers[$component] = $resolver;
            }
            else $resolver = $this->__resolvers[$component];
        }

        return $resolver;
    }
}