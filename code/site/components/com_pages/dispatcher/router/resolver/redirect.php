<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Dispactcher Redirect Route Resolver
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Resolver
 */
class ComPagesDispatcherRouterResolverRedirect extends ComPagesDispatcherRouterResolverAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_HIGH,
        ));

        parent::_initialize($config);
    }

    /**
     * Resolve the request
     *
     * If the requuest resolves set the response to a 301 redirect.
     *
     * @param ComPagesDispatcherRouterInterface $router
     * @return false|KHttpUrl Returns the matched route or false if no match was found
     */
    public function resolve(ComPagesDispatcherRouterInterface $router)
    {
        if($route = parent::resolve($router))
        {
            //Set the location header
            $router->getResponse()->getHeaders()->set('Location', (string) $router->qualifyUrl($route));

            //Set the 301 status
            $router->getResponse()->setStatus(KHttpResponse::MOVED_PERMANENTLY);
        }

        return $route;
    }

    /**
     * Reversed routing
     *
     * @param string $path The path to generate a route for
     * @param array $query @params Associative array of parameters to replace placeholders with.
     * @param ComPagesDispatcherRouterInterface $router
     * @return false|KHttpUrl Returns the generated route
     */
    public function generate($page, array $query, ComPagesDispatcherRouterInterface $router)
    {
        //Do not allow for reverse routing
        return false;
    }
}