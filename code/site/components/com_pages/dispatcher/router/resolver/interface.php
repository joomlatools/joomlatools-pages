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
     * @return bool
     */
    public function resolve(ComPagesDispatcherRouterRouteInterface $route);

    /**
     * Reversed routing
     *
     * @param ComPagesDispatcherRouterInterface $route The route to generate
     * @return bool
     */
    public function generate(ComPagesDispatcherRouterRouteInterface $route);
}
