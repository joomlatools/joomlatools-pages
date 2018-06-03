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

        if(isset($query['path']))
        {
            $segments[] = $query['path'];
            unset($query['path']);
        }

        if(isset($query['file']))
        {
            $segments[] = $query['file'];
            unset($query['file']);
        }

        return $segments;
    }

    public function parse($segments)
    {
        $result = array('view' => 'page');

        //Replace all the ':' with '-' again
        $segments = array_map(function($segment) {
            return str_replace(':', '-', $segment);
        }, $segments);

        if(!empty($segments))
        {
            $file = array_pop($segments);
            $path = implode('/', $segments);
        }
        else
        {
            $file = 'index';
            $path = '';
        }

        $result['file'] = $file;
        $result['path'] = $path ?: '.';

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