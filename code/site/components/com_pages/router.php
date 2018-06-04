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

    public function build(&$query)
    {
        $segments = array();
        if (isset($query['view'])) {
            unset($query['view']);
        }

        if(isset($query['file'])) {
            unset($query['file']);
        }

        if(isset($query['path']))
        {
            $segments[] = $query['path'];
            unset($query['path']);
        }

        return $segments;
    }

    public function parse($segments)
    {
        $result = array('view' => 'page');
        $route  = array();

        //Replace all the ':' with '-' again
        $segments = array_map(function($segment) {
            return str_replace(':', '-', $segment);
        }, $segments);

        //Find the path and file
        $parts = array();
        $page = KObjectManager::getInstance()->getObject('com:pages.template.page');

        foreach($segments as $segment)
        {
            $parts[] = $segment;

            if(!$page->findFile(implode($parts, '/')))
            {
                array_pop($parts);
                $segments = array_values(array_diff($segments, $parts));

                //Get the route
                $page->loadFile(implode($parts, '/'));

                if($page->isCollection()) {
                    $route = $page->parseRoute($segments);
                }

                break;
            }
        }

        //If no file element exists get the last part
        if(!$route['file']) {
            $result['file'] = array_pop($parts);
        }

        //Create the path
        $result['path'] = implode($parts, '/') ?: '.';

        //Merge the route variables
        $result = array_merge($result, $route);

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