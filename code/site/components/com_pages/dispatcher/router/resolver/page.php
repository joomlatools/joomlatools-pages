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
        if($result = parent::resolve($route))
        {
            if($state = $route->getState()) {
                $this->_resolvePagination($route, $state);
            }
        }

        return $result;
    }

    public function generate(ComPagesDispatcherRouterRouteInterface $route)
    {
        if($result = parent::generate($route))
        {
            if($state = $route->getState()) {
                $this->_generatePagination($route, $state);
            }
        }

        return $result;
    }

    protected function _resolvePagination(ComPagesDispatcherRouterRouteInterface $route, $state)
    {
        if(isset($state['limit']))
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