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

        //Slug
        if(isset($query['page'])) {
            unset($query['page']);
        }

        //Path
        if(isset($query['path']))
        {
            //Remove hardcoded states
            if($collection = $this->getRegistry()->isCollection($query['path']))
            {
                if(isset($collection['state'])) {
                    $query = array_diff_key($query, $collection['state']);
                }
            }

            $segments[] = $query['path'];
            unset($query['path']);
        }

        //Format
        if(isset($query['format'])) {
            JFactory::getConfig()->set('sef_suffix', 1);
        }

        return $segments;
    }

    public function parse($segments)
    {
        $query = array();

        //Replace all the ':' with '-' again
        $segments = array_map(function($segment) {
            return str_replace(':', '-', $segment);
        }, $segments);


        //Format
        $page = array_pop($segments);
        if($format = pathinfo($page, PATHINFO_EXTENSION))
        {
            $query['format'] = $format;
            $segments[] = basename($page, '.'.$format);
        }
        else $segments[] = $page;

        //Path and page
        $route = implode($segments, '/');
        if($this->getRegistry()->isPage($route))
        {
            if($collection = $this->getRegistry()->isCollection($route))
            {
                $query['path'] = $route;

                //Add hardcoded states
                if(isset($collection['state'])) {
                    $query = array_merge($query, $collection['state']);
                }
            }
            else
            {
                $query['page'] = array_pop($segments);
                $query['path'] = implode($segments, '/') ?: '.';
            }
        }

        return $query;
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