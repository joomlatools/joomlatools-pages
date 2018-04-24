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
        if (isset($query['view'])) {
            unset($query['view']);
        }

        return array();
    }

    public function parse($segments)
    {
        return array('view' => 'page');
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