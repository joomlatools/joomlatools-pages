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
                $package = $route->getScheme();
            } else {
                $package = $route->getIdentifier()->getPackage();
            }
        }
        else $package = parse_url($route, PHP_URL_SCHEME);

        if($package)
        {
            if (!isset($this->__resolvers[$package]))
            {
                $config     = ['request' => $this->getRequest()];
                $identifier = 'com://site/'.$package.'.dispatcher.router.'.$package;

                $resolver = $this->getObject($identifier, $config);

                if (!($resolver instanceof ComPagesDispatcherRouterInterface))
                {
                    throw new UnexpectedValueException(
                        "Resolver $identifier does not implement DispatcherRouterInterface"
                    );
                }

                $this->__resolvers[$package] = $resolver;
            }
            else $resolver = $this->__resolvers[$package];
        }

        return $resolver;
    }
}