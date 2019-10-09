<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterResolverPage extends ComPagesDispatcherRouterResolverRegex
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'routes' => $this->getObject('page.registry')->getRoutes(),
        ));

        parent::_initialize($config);
    }

    public function resolve(ComPagesDispatcherRouterRouteInterface $route)
    {
        if($route = parent::resolve($route))
        {
            $page = $route->getPath();

            if($page = $this->getObject('page.registry')->getPage($page))
            {
                if($collection = $page->isCollection())
                {
                    if(isset($collection['state'])) {
                        $this->_resolvePagination($route, $collection['state']);
                    }
                }
            }
        }

        return $route;
    }

    public function generate(ComPagesDispatcherRouterRouteInterface $route)
    {
        $page = $route->getPath();

        if($route = parent::generate($route))
        {
            //Remove hardcoded collection states
            if($page = $this->getObject('page.registry')->getPage($page))
            {
                if(($collection = $page->isCollection()) && isset($collection['state']))
                {
                    //Remove any hardcoded states from the generated route
                    $route->query = array_diff_key($route->query, $collection['state']);

                    if(isset($collection['state'])) {
                        $this->_generatePagination($route, $collection['state']);
                    }
                }
            }
        }

        return $route;
    }

    protected function _resolvePagination(ComPagesDispatcherRouterRouteInterface $route, $state)
    {
        if( isset($state['limit']))
        {
            if(isset($route->query['page']))
            {
                $limit = $state['limit'];
                $page  = $route->query['page'] - 1;

                $route->query['offset'] = $page * $limit;

                unset($route->query['page']);
            }
        }
    }

    protected function _generatePagination(ComPagesDispatcherRouterRouteInterface $route, $state)
    {
        if(isset($state['limit']))
        {
            if(isset($route->query['offset']))
            {
                $limit = $state['limit'];

                if($offset = $route->query['offset']) {
                    $route->query['page'] = $offset/$limit + 1;
                }

                unset($route->query['offset']);
            }
        }
    }
}