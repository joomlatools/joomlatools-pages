<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

trait ComPagesViewTraitLocatable
{
    public function getUrl($url = null)
    {
        if(!empty($url))
        {
            if($url instanceof KHttpUrlInterface)
            {
                $result = clone $url;
                $result->setUrl(parent::getUrl()->toString(KHttpUrl::AUTHORITY));
            }
            else
            {
                $result = clone parent::getUrl();;
                $result->setUrl($url);
            }
        }
        else $result = parent::getUrl();

        return $result;
    }

    public function getRoute($route = null, $query = array(), $escape = false)
    {
        $result = null;

        if($route)
        {
            //Prepend the route with the package
            if(is_string($route) && strpos($route, ':') === false) {
                $route = $this->getIdentifier()->getPackage().':'.$route;
            }

            //Generate the route
            $router = $this->getObject('dispatcher')->getRouter();

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