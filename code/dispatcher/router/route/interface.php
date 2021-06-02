<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Dispatcher Router Route Interface
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Route
 */
interface ComPagesDispatcherRouterRouteInterface extends KHttpUrlInterface
{
    const STATUS_RESOLVED  = 1;
    const STATUS_GENERATED = 2;

    /**
     * Get a route parameter
     *
     * @param string $name The parameter name
     * @param mixed $default The parameter default value
     * @return mixed
     */
    public function get($name, $default = null);

    /**
     * Check if a route parameter exists
     *
     * @param string $name The parameter name
     * @return bool
     */
    public function has($name);

    /**
     * Set the route parameters
     *
     * @param array $parameters An associative array of route parameters
     * @return $this
     */
    public function setParameters($parameters);

    /**
     * Get the route parameters
     *
     * @return array
     */
    public function getParameters();

    /**
     * Get the route state
     *
     * @return array
     */
    public function getState();

    /**
     * Get the format
     *
     * @return string
     */
    public function getFormat();

    /**
     * Mark the route as resolved
     *
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function setResolved();

    /**
     * Mark the route as generated
     *
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function setGenerated();

    /**
     * Test if the route has been resolved
     *
     * @return	bool
     */
    public function isResolved();

    /**
     * Test if the route has been generated
     *
     * @return	bool
     */
    public function isGenerated();

    /**
     * Test if the route is absolute
     *
     * @return	bool
     */
    public function isAbsolute();
}
