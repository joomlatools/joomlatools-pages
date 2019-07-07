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
        ));

        parent::_initialize($config);
    }

    public function getPath(ComPagesDispatcherRouterInterface $router)
    {
        $path   = parent::getPath($router);
        $format = $router->getResponse()->getRequest()->getFormat();

        //Append the format
        if($format !== 'html' && strpos($path,  '.'.$format) == false ) {
            $path .= '.'.$format;
        }

        return $path;
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
                if($collection = $page->isCollection())
                {
                    //Set collection states
                    if(isset($collection['state']) && isset($collection['state']['limit'])) {
                        $this->_resolvePagination($request, $collection['state']['limit']);
                    }
                }
            }

            if(!$canonical = $page->canonical)
            {
                //Add a (self-referential) canonical URL using the first route for the specific page
                if($routes = $this->getObject('page.registry')->getRoutes($route->getPath()))
                {
                    //Build the route
                    $canonical = $this->buildRoute($routes[0],  $request->query->toArray());

                    if($collection = $page->isCollection())
                    {
                        //Handle pagination
                        if(isset($collection['state']) && isset($collection['state']['limit'])) {
                            $this->_generatePagination($canonical, $collection['state']['limit']);
                        }
                    }

                    $canonical = $router->qualifyUrl($canonical);

                    //Set the canonical in the page
                    $page->canonical = $canonical;
                }
            }

            if($canonical) {
                $router->setCanonicalUrl($canonical);
            }
        }

        return $route;
    }

    public function generate($page, array $query, ComPagesDispatcherRouterInterface $router)
    {
        if($page instanceof ComPagesModelEntityPage) {
            $page = $page->route;
        }

        $page = ltrim($page, './');

        if($url = parent::generate($page, $query, $router))
        {
            //Remove hardcoded collection states
            if($page = $this->getObject('page.registry')->getPage($page))
            {
                if(($collection = $page->isCollection()) && isset($collection['state']))
                {
                    $url->query = array_diff_key($url->query, $collection['state']);

                    //Handle pagination
                    if(isset($collection['state']['limit'])) {
                        $this->_generatePagination($url, $collection['state']['limit']);
                    }
                }
            }
        }

        return $url;
    }

    protected function _resolvePagination(KDispatcherRequestInterface $request, $limit)
    {
        if(isset($request->query['page']))
        {
            $page = $request->query['page'] - 1;
            $request->query['offset'] = $page * $limit;

            unset($request->query['page']);
        }
    }

    protected function _generatePagination(KHttpUrlInterface $url, $limit)
    {
        if(isset($url->query['offset']))
        {
            if($offset = $url->query['offset']) {
                $url->query['page'] = $offset/$limit + 1;
            }

            unset($url->query['offset']);
        }
    }
}