<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesRouter extends KObject implements KObjectSingleton
{
    public function build(&$query)
    {
        $segments = array();

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
            $segments[] = $query['path'];
            unset($query['path']);
        }

        //Slug
        if(isset($query['slug']))
        {
            //Handle frontpage
            if($query['slug'] != 'index') {
                $segments[] = $query['slug'];
            }

            unset($query['slug']);
        }

        //Format
        if(isset($query['format'])) {
            JFactory::getConfig()->set('sef_suffix', 1);
        }

        //Format
        if(isset($query['view'])) {
            unset($query['view']);
        }

        return $segments;
    }

    public function parse($route)
    {
        $query = array();

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
        }

        return $query;
    }

    public function getRoute()
    {
        //Setup the pathway
        $request = $this->getObject('request');

        $base = $request->getBasePath();
        $url  = $request->getUrl()->getPath();

        //Get the segments
        $path = trim(str_replace(array($base, '/index.php'), '', $url), '/');

        return $path;
    }
}

function PagesBuildRoute(&$query)
{
    return KObjectManager::getInstance()->getObject('com:pages.router')->build($query);
}

function PagesParseRoute($segments)
{
    return array();
    //return KObjectManager::getInstance()->getObject('com:pages.router')->parse($segments);
}