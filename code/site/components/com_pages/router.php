<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/textman for the canonical source repository
 */


class ComPagesRouter
{
    /**
     * Private constructor to avoid direct instantiation
     */
    private function __construct() {}

    /**
     * Returns an instance of the class
     *
     * @return ComPagesRouter
     */
    public static function getInstance()
    {
        static $instance;

        if (!$instance) {
            $instance = new ComPagesRouter();
        }

        return $instance;
    }
    /**
     * Builds a URL from a query object
     *
     * @param array $query query object
     *
     * @return array
     */
    public function build(&$query)
    {
        if (isset($query['view'])) {
            unset($query['view']);
        }

        return array();
    }


    /**
     * Parse the segments into query string
     *
     * @param array $segments
     * @return array
     */
    public function parse($segments)
    {
        return array('view' => 'page');
    }
}

/**
 * Hooks up router to Joomla URL build event
 *
 * @param array $query
 * @return array
 */
function PagesBuildRoute(&$query)
{
    return ComPagesRouter::getInstance()->build($query);
}

/**
 * Hooks up router to Joomla URL parse event
 *
 * @param array $segments
 * @return array
 */
function PagesParseRoute($segments)
{
    return ComPagesRouter::getInstance()->parse($segments);
}