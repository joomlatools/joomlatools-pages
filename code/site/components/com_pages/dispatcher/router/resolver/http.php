<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Dispatcher Http Route Resolver
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Resolver
 */
class ComPagesDispatcherRouterResolverHttp extends ComPagesDispatcherRouterResolverAbstract
{
    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config Configuration options
     * @return 	void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_LOW,
        ));

        parent::_initialize($config);
    }

    /**
     *  Resolve the request
     *
     * @param ComPagesDispatcherRouterInterface $router
     * @return false|KHttpUrl Returns the matched route or false if no match was found
     */
    public function resolve(ComPagesDispatcherRouterInterface $router)
    {
        if($route = parent::resolve($router))
        {
            //Set the matched params in the request query
            foreach($route->query as $key => $value) {
                $router->getResponse()->getRequest()->query->set($key, $value);
            }
        }

        return $route;
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a route. Replace regexes with supplied parameters
     *
     * @param string $path The path to generate a route for
     * @param array @params Associative array of parameters to replace placeholders with.
     * @return false|KHttpUrl Returns the generated route
     */
    public function generate($path, array $query, ComPagesDispatcherRouterInterface $router)
    {
        $parts = explode('#', $path);

        if($url = parent::generate($parts[0], $query, $router))
        {
            if($parts[1]) {
                $url->setFragment($parts[1]);
            }
        }

        return $url;
    }
}