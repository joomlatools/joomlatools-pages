<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesRouter
{
    private function __construct() {}

    public static function getInstance()
    {
        static $instance;

        if (!$instance) {
            $instance = new ComPagesRouter();
        }

        return $instance;
    }

    public function getRegistry()
    {
        return KObjectManager::getInstance()->getObject('page.registry');
    }

    public function build(&$query)
    {
        $segments = array();
        if(isset($query['file'])) {
            unset($query['file']);
        }

        if(isset($query['path']))
        {
            //Remove hardcoded states
            if($page = $this->getRegistry()->getPage($query['path']))
            {
                if($collection = $page->isCollection()) {
                    $query = array_diff_key($query, $collection['state']);
                }
            }

            $segments[] = $query['path'];
            unset($query['path']);
        }

        if(isset($query['route']))
        {
            $segments[] = $query['route'];
            unset($query['route']);
        }

        return $segments;
    }

    public function parse($segments)
    {
        $result = array();
        $route  = array();

        //Replace all the ':' with '-' again
        $segments = array_map(function($segment) {
            return str_replace(':', '-', $segment);
        }, $segments);

        //Find the path
        $parts = array();
        foreach($segments as $segment)
        {
            $parts[] = $segment;

            if(!$this->getRegistry()->hasPage(implode($parts, '/')))
            {
                array_pop($parts);
                break;
            }
        }

        $page = $this->getRegistry()->getPage(implode($parts, '/'));

        if($collection = $page->isCollection())
        {
            $segments = array_values(array_diff($segments, $parts));

            //Parse the route
            if($segments && isset($collection['route']))
            {
                $query  = $this->parseCollection($segments, $collection['route']);
                $result = array_merge($result, $query);
            }

            //Merge the state
            if(isset($collection['state'])) {
                $result = array_merge($result, $collection['state']);
            }
        }
        else $result['file'] = array_pop($parts);

        $result['path'] = implode($parts, '/') ?: '.';

        return $result;
    }

    public function parseCollection(array $segments, $route)
    {
        $result = array();

        $parts    = explode('/', $route);
        $segments = array_values($segments);

        foreach($parts as $key => $name)
        {
            if($name[0] == ':' && isset($segments[$key]))
            {
                $name = ltrim($name, ':');
                $result[$name] = $segments[$key];
            }
        }

        return $result;
    }
}

function PagesBuildRoute(&$query)
{
    return ComPagesRouter::getInstance()->build($query);
}

function PagesParseRoute($segments)
{
    return ComPagesRouter::getInstance()->parse($segments);
}