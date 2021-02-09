<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * State Dispatcher Route Resolver
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Resolver
 */
class ComPagesDispatcherRouterResolverState extends ComPagesDispatcherRouterResolverAbstract
{
    public function resolve(ComPagesDispatcherRouterRouteInterface $route)
    {
        //Remove any query parameters that are not states from the resolved route
        if($page = $route->getPage())
        {
            if($page->isCollection())
            {
                $state = $this->getObject('model.factory')
                    ->createModel($page->path)
                    ->getState();

                $route->query = array_intersect_key($route->query, $state->toArray());
            }
            else $route->query = $route->getParameters();
        }
    }

    public function generate(ComPagesDispatcherRouterRouteInterface $route)
    {
        if($page = $route->getPage())
        {
            //Remove any hardcoded states from the generated route
            if(($collection = $page->isCollection()) && isset($collection['state'])) {
                $route->query = array_diff_key($route->query, $collection['state']);
            }
        }
    }
}
