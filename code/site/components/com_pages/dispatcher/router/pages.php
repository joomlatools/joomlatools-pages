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
    public function resolve($route, array $parameters = array())
    {
        $route = $this->getRoute($route, $parameters);
        return parent::resolve($route, $parameters);
    }

    public function generate($route, array $parameters = array())
    {
        $route = $this->getRoute($route, $parameters);
        return parent::generate($route, $parameters);
    }

    public function getRoute($route, array $parameters = array())
    {
        if($route instanceof ComPagesModelEntityPage) {
            $route = 'pages:'.$route->path;
        }

        return parent::getRoute($route, $parameters);
    }
}