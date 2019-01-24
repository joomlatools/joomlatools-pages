<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Dispatcher Route Resolver Interface
 *
 * Provides route building and parsing functionality
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Resolver
 */
interface ComPagesDispatcherRouterResolverInterface
{
    /**
     * Priority levels
     */
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH    = 2;
    const PRIORITY_NORMAL  = 3;
    const PRIORITY_LOW     = 4;
    const PRIORITY_LOWEST  = 5;

    /**
     * Get the priority of the resolver
     *
     * @return  integer The resolver priority
     */
    public function getPriority();

    /**
     * Add a route for matching
     *
     * If only a path is defined the route is considered a static route
     *
     * @param string $route The route regex You can use multiple pre-set regex filters, like [digit:id]
     * @param string $path The path this route should point to.
     * @return ComPagesDispatcherRouterResolverInterface
     */
    public function addRoute($route, $path);

    /**
     * Add routes for matching
     *
     * @param array $routes  The routes to be added
     * @return ComPagesDispatcherRouterResolverInterface
     */
    public function addRoutes($routes);

    /**
     *  Resolve the request
     *
     * @param ComPagesDispatcherRouterInterface $router
     * @return false|KHttpUrl Returns the matched route or false if no match was found
     */
    public function resolve(ComPagesDispatcherRouterInterface $router);

    /**
     * Reversed routing
     *
     * @param string $path The path to generate a route for
     * @param array $query @params Associative array of parameters to replace placeholders with.
     * @param ComPagesDispatcherRouterInterface $router
     * @return false|KHttpUrl Returns the generated route
     */
    public function generate($path, array $query, ComPagesDispatcherRouterInterface $router);
}
