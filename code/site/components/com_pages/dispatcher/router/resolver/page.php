<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterResolverPage extends ComPagesDispatcherRouterResolverHttp
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_NORMAL,
            'routes'   => $this->getObject('page.registry')->getRoutes()
        ));

        parent::_initialize($config);
    }

    public function resolve(ComPagesDispatcherRouterInterface $router)
    {
        if($route = parent::resolve($router))
        {
            $response = $router->getResponse();
            $request  = $response->getRequest();
            $path     = $route->getPath();

            if($page = $this->getObject('page.registry')->getPage($path))
            {
                $query = array();
                if($collection = $page->isCollection())
                {
                    //Set collection states
                    if(isset($collection['state']))
                    {
                        //Handle pagination
                        if (isset($collection['state']['limit']) && isset($request->query['page']))
                        {
                            $limit = $collection['state']['limit'];
                            $page = $request->query['page'] - 1;

                            $query['offset'] = $page * $limit;

                            unset($query['page']);
                        }
                    }

                    //Set the params in the query overwriting existing values
                    foreach($query as $key => $value) {
                        $request->query->set($key, $value);
                    }
                }
            }

            //Add a (self-referential) ccanonical URL using the first route for the specific page
            if($routes = $this->getObject('page.registry')->getRoutes($route->getPath()))
            {
                //Build the route
                $url = $this->buildRoute($routes[0],  $route->getQuery(true));

                //Qualify the url
                $url = $router->qualifyUrl($url);

                $router->setCanonicalUrl($url);
            }
        }

        return $route;
    }

    public function generate($page, array $query, ComPagesDispatcherRouterInterface $router)
    {
        if($page instanceof ComPagesModelEntityPage) {
            $page = $page->route;
        }

        if($url = parent::generate($page, $query, $router))
        {
            //Remove hardcoded collection states
            if($page = $this->getObject('page.registry')->getPage($page))
            {
                if(($collection = $page->isCollection()) && isset($collection['state']))
                {
                    $url->query = array_diff_key($url->query, $collection['state']);

                    //Handle pagination
                    if(isset($collection['state']['limit']) && isset($url->query['offset']))
                    {
                        if($offset = $url->query['offset'])
                        {
                            $limit = $collection['state']['limit'];
                            $url->query['page'] = $offset/$limit + 1;
                        }

                        unset($url->query['offset']);
                    }
                }
            }
        }

        return $url;
    }
}