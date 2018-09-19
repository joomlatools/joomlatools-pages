<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

/**
 * Dispatcher Route
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Component\Koowa\Dispatcher\Router
 */
class ComPagesDispatcherRouterRoute extends KDispatcherRouterRoute
{
    public function toString($parts = self::FULL, $escape = null)
    {
        $url = parent::toString($parts, $escape);

        //Quality the url
        if($this->getObject('request')->getFormat() != 'html')
        {
            $this->scheme = $this->getObject('request')->getUrl()->scheme;
            $this->host   = $this->getObject('request')->getUrl()->host;
            $this->port   = $this->getObject('request')->getUrl()->port;
        }

        return $url;
    }

    public function build($query)
    {
        //Base
        $path[] = $this->getObject('request')->getBasePath();

        if(strpos($this->getObject('request')->getUrl(), 'index.php') !== false) {
            $path[] = 'index.php';
        }

        //Path
        if(isset($query['path']))
        {
            //Remove hardcoded states
            if($collection = $this->getObject('page.registry')->isCollection($query['path']))
            {
                if(isset($collection['state'])) {
                    $query = array_diff_key($query, $collection['state']);
                }
            }

            //Handle frontpage
            $path[] =  $query['path'];
            unset($query['path']);
        }

        //Slug
        if(isset($query['slug']))
        {
            //Handle frontpage
            if( $query['slug'] != 'index') {
                $path[] = $query['slug'];
            }

            unset( $query['slug']);
        }

        //Format
        if(isset($query['view'])) {
            unset($query['view']);
        }

        $this->setPath(implode('/', $path));
        $this->setQuery($query);

        return $this;
    }

    public function parse($url = null)
    {
        $this->setUrl($url);

        $query = array();

        //Get the route
        $route = $this->getRoute();

        //Parse the format
        $page = $route ?: 'index';

        if($this->getObject('page.registry')->isPage($page))
        {
            if ($format = pathinfo($route, PATHINFO_EXTENSION))
            {
                $query['format'] = $format;
                $route = basename($route, '.' . $format);
            }
            else $route = $page;

            $query['page'] = $page;

            if($collection = $this->getObject('page.registry')->isCollection($route))
            {
                $query['path']   = $route;
                $query['layout'] = $route;

                //Add hardcoded states
                if(isset($collection['state'])) {
                    $query = array_merge($query, $collection['state']);
                }
            }
            else
            {
                $segments = explode('/', $route);

                $query['slug'] = array_pop($segments);
                $query['path'] = implode($segments, '/') ?: '.';
            }

            return $query;
        }

        return false;
    }

    public function getRoute()
    {
        $base = $this->getObject('request')->getBasePath();
        $url  = $this->getPath();

        //Get the route
        return trim(str_replace(array($base, '/index.php'), '', $url), '/');
    }
}