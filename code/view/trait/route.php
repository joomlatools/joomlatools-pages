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
    public function getRoute($entity = null, $fqr = true, $escape = null)
    {
        $query  = [];
        $result = null;

        if($entity)
        {
            //Resolve entity
            if($entity instanceof ComPagesModelEntityInterface)
            {
                if(!$entity instanceof ComPagesModelEntityPages && !$entity instanceof ComPagesModelEntityPage)
                {
                    $collection = $entity->getModel()->getCollection();

                    $path = false;
                    if($collection->route !== false)
                    {
                        $path = $collection->route;
                        $page = $this->getPage('/'.$collection->route);

                        if($routes = (array) KObjectConfig::unbox($page->route))
                        {
                            //Check if dynamic route
                            if(strpos($routes[0], ':') !== false)
                            {
                                $matches = array();
                                preg_match_all('#:(.+?)]#', $routes[0], $matches);

                                $params = $matches[1];
                            }
                            //Use primary key as fallback
                            else  $params = $entity->getModel()->getPrimaryKey();

                            foreach($params as $param)
                            {
                                if($entity->hasProperty($param))
                                {
                                    $value = $entity->getProperty($param);

                                    if($value instanceof ComPagesModelEntityInterface) {
                                        $query[$param] = $value->{$value->getIdentityKey()};
                                    } else {
                                        $query[$param] = $value;
                                    }
                                }
                                else
                                {
                                    if($this->getState()->has($param)) {
                                        $query[$param] = $this->getState()->get($param);
                                    }
                                }
                            }
                        }
                    }
                }
                else $path = $entity->path;
            }
            else $path = (string) $entity;

            //Route path
            if($path)
            {
                //Generate the route
                $router = $this->getObject('router');

                //Prepend the route with the package
                if(is_string($path) && strpos($path, ':') === false) {
                    $path = $this->getIdentifier()->getPackage().':'.$path;
                }

                if($route = $router->generate($path, $query))
                {
                    //Determine if we should escape the route
                    if($escape === null && $this->getFormat() !== 'json') {
                        $escape = true;
                    }

                    $route  = $router->qualify($route)->setEscape($escape);
                    $result = $route->toString($this->getFormat() !== 'html' ? KHttpUrl::FULL : KHttpUrl::PATH + KHttpUrl::QUERY + KHttpUrl::FRAGMENT);
                }
            }

        }
        else $result = $this->getObject('dispatcher')->getRoute();

        return $result;
    }
}