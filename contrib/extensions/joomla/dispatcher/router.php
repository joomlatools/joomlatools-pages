<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaDispatcherRouter extends ComPagesDispatcherRouterAbstract
{
    public function getRoute($route, array $parameters = array())
    {
        if(is_object($route) && is_callable([$route, 'getRoute'])) {
            $route = $route->getRoute();
        }

        $route = parent::getRoute($route, $parameters);
        $route->setGenerated();

        return $route;
    }
}