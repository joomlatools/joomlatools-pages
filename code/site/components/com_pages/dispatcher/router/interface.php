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
     * Resolve a route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface|KObjectInterface $route The route to resolve
     * @param array $parameters Route parameters
     * @return false| ComPagesDispatcherRouterInterface Returns the matched route or false if no match was found
     */
    public function resolve($route, array $parameters = array());

    /**
     * Generate a route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface|KObjectInterface $route The route to resolve
     * @param array $parameters Route parameters
     * @return false|KHttpUrlInterface Returns the generated route
     */
    public function generate($route, array $parameters = array());

    /**
     * Qualify a route
     *
     * Replace the url authority with the authority of the request url
     * @param ComPagesDispatcherRouterRouteInterface $route The route to qualify
     * @param   bool  $replace If the url is already qualified replace the authority
     * @return string
     */
    public function qualify(ComPagesDispatcherRouterRouteInterface $route,  $replace = false);

    /**
     * Get a route resolver
     *
     * @param   mixed   $resolver  KObjectIdentifier object or valid identifier string
     * @param   array $config  An optional associative array of configuration settings
     * @throws UnexpectedValueException
     * @return  ComPagesDispatcherRouterAbstract
     */
    public function getResolver($resolver, $config = array());

    /**
     * Get the list of attached resolvers
     *
     * @return array
     */
    public function getResolvers();

    /**
     * Attach a route resolver
     *
     * @param   mixed  $resolver An object that implements ObjectInterface, ObjectIdentifier object
     *                            or valid identifier string
     * @param   array $config  An optional associative array of configuration settings
     * @param  bool $prepend If true, the resolver will be prepended instead of appended.
     * @return  ComPagesDispatcherRouterAbstract
     */
    public function attachResolver($resolver, $config = array(), $prepend = false);

    /**
     * Get a route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface $route The route to resolve
     * @param array $parameters Route parameters
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function getRoute($route, array $parameters = array());

    /**
     * Set the request object
     *
     * @param KControllerRequestInterface $request A request object
     * @return ComPagesDispatcherRouterInterface
     */
    public function setRequest(KControllerRequestInterface $request);

    /**
     * Get the request object
     *
     * @return KControllerRequestInterface
     */
    public function getRequest();
}
