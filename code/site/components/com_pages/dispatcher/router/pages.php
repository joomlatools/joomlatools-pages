<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterPages extends ComPagesDispatcherRouterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'route'  => 'page',
            'routes' => $this->getObject('page.registry')->getRoutes(),
        ])->append(([
            'resolvers' => [
                'pagination',
                'regex' => ['routes' => $config->routes],
            ]
        ]));

        parent::_initialize($config);
    }

    public function getRoute($route, array $parameters = array())
    {
        if($route instanceof ComPagesModelEntityPage) {
            $route = 'page:'.$route->path;
        }

        return parent::getRoute($route, $parameters);
    }
}