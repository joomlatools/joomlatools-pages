<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Dispatcher Router Interface
 *
 * Provides route building and parsing functionality
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router
 */
interface ComPagesDispatcherRouterInterface
{
    /**
     * Resolve the request
     *
     * @return false|KHttpUrl Returns the matched route or false if no match was found
     */
    public function resolve();

    /**
     * Generate a route
     *
     * @param string $path The path to generate a route for
     * @param array @params Associative array of parameters to replace placeholders with.
     * @return KHttpUrl Returns the generated route
     */
    public function generate($path, array $query = array());

    /**
     * Get the request path
     *
     * @return string
     */
    public function getPath();

    /**
     * Qualify a url
     *
     * @param KhttpUrl $url The url to qualify
     * @return KHttpUrl
     */
    public function qualifyUrl(KHttpUrl $url);

    /**
     * Set the response object
     *
     * @param KControllerResponseInterface $response A response object
     * @return ComPagesDispatcherRouterInterface
     */
    public function setResponse(KControllerResponseInterface $response);

    /**
     * Get the response object
     *
     * @return KControllerResponseInterface
     */
    public function getResponse();

    /**
     * Get the canonical url
     *
     * @return  KHttpUrl|null  A HttpUrl object or NULL if no canonical url could be found
     */
    public function getCanonicalUrl();

    /**
     * Sets the canonical url
     *
     * @param  string|KHttpUrlInterface $canonical
     * @return ComPagesDispatcherRouterInterface
     */
    public function setCanonicalUrl($canonical);

    /**
     * Get a resolver handler by identifier
     *
     * @param   mixed $resolver An object that implements ObjectInterface, ObjectIdentifier object
     *                                 or valid identifier string
     * @param   array $config An optional associative array of configuration settings
     * @throws UnexpectedValueException
     * @return ComPagesDispatcherRouterInterface
     */
    public function getResolver($resolver, $config = array());

    /**
     * Attach a router resolver
     *
     * @param   mixed  $resolver An object that implements ObjectInterface, ObjectIdentifier object
     *                            or valid identifier string
     * @param   array $config  An optional associative array of configuration settings
     * @return ComPagesDispatcherRouterInterface
     */
    public function attachResolver($resolver, $config = array());
}
