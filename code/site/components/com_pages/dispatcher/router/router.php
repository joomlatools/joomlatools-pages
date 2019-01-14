<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

class ComPagesDispatcherRouter extends ComPagesDispatcherRouterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'routes'  => $this->getObject('page.registry')->getRoutes()
        ));

        parent::_initialize($config);
    }

    public function getPage()
    {
        return $this->match();
    }

    public function match()
    {
        $page = false;

        if($route = parent::match())
        {
            $path = $route->getPath();

            if($page = $this->getObject('page.registry')->getPage($path))
            {
                $query = array();
                if(isset($page->collection) && $page->collection !== false)
                {
                    //Set path
                    $query['path'] = $path;

                    //Set collection states to request
                    if(isset($page->collection->state)) {
                        $query = array_merge($query, KObjectConfig::unbox($page->collection->state));
                    }

                    //Set route states in page collection
                    foreach($route->query as $key => $value) {
                        $page->collection->state->set($key, $value);
                    }
                }
                else
                {
                    $segments = explode('/', $path);

                    //Set path and slug
                    $query['slug'] = array_pop($segments);
                    $query['path'] = implode($segments, '/') ?: '.';
                }

                //Set the params in the query overwriting existing values
                foreach($query as $key => $value) {
                    $this->getRequest()->query->set($key, $value);
                }
            }
        }

        return $page;
    }

    public function generate($page, array $query = array(), $escape = true)
    {
        $url = parent::generate($page, $query, $escape);

        //Remove hardcoded collection states
        if($page = $this->getObject('page.registry')->getPage($page))
        {
            if(($collection = $page->isCollection()) && isset($collection['state'])) {
                $url->query = array_diff_key($url->query, $collection['state']);
            }
        }

        ///Remove hardcoded model states
        unset($url->query['path']);
        unset($url->query['slug']);
        unset($url->query['view']);

        return $url;
    }
}