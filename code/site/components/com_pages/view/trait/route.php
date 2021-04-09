<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

trait ComPagesViewTraitRoute
{
    public function getRoute($route = null, $query = array(), $escape = null)
    {
        $result = null;

        //Determine if we should escape the route
        if($escape === null && $this->getFormat() !== 'json') {
            $escape = true;
        }

        if($route)
        {
            //Prepend the route with the package
            if(is_string($route) && strpos($route, ':') === false) {
                $route = $this->getIdentifier()->getPackage().':'.$route;
            }

            //Generate the route
            $router = $this->getObject('router');

            if($route = $router->generate($route, $query))
            {
                $route  = $router->qualify($route)->setEscape($escape);
                $result = $route->toString($this->getFormat() !== 'html' ? KHttpUrl::FULL : KHttpUrl::PATH + KHttpUrl::QUERY + KHttpUrl::FRAGMENT);
            }
        }
        else $result = $this->getObject('dispatcher')->getRoute();

        return $result;
    }
}