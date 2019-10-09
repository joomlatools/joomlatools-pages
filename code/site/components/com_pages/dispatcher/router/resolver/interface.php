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
     *  Resolve the route
     *
     * @param ComPagesDispatcherRouterInterface $route The route to resolve
     * @return false| ComPagesDispatcherRouterInterface Returns the matched route or false if no match was found
     */
    public function resolve(ComPagesDispatcherRouterRouteInterface $route);

    /**
     * Reversed routing
     *
     * @param ComPagesDispatcherRouterInterface $route The route to generate
     * @return false|KHttpUrl Returns the generated route
     */
    public function generate(ComPagesDispatcherRouterRouteInterface $route);
}
