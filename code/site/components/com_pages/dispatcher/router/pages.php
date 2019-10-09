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
    public function compile($route, array $parameters = array())
    {
        if($route instanceof ComPagesModelEntityPage) {
            $route = 'pages:'.$route->path;
        }

        return parent::compile($route, $parameters);
    }

    public function getResolver($route)
    {
        //Set page as default resolver
        if(!$route->getHost()) {
            $route->setHost('page');
        }

        return parent::getResolver($route);
    }
}