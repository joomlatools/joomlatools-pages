<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */


/**
 * Abstract Dispatcher Route Resolver
 *
 * Inspired by Altorouter: https://github.com/dannyvankooten/AltoRouter
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Resolver
 */
abstract class ComPagesDispatcherRouterResolverAbstract extends KObject implements ComPagesDispatcherRouterResolverInterface
{
    /**
     * The resolver priority
     *
     * @var integer
     */
    protected $_priority;

    /**
     * Static routes
     *
     * @var array
     */
    private $__static_routes = array();

    /**
     * Dynamic routes
     *
     * @var array
     */
    private $__dynamic_routes = array();

    /**
     * Array of default match types (regex helpers)
     *
     * @var array
     */
    protected $_match_types;

    /**
     * Constructor
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_priority = $config->priority;

        // The match types
        $this->_match_types = $config->types;

        //Add the routes
        $this->addRoutes($config->routes);
    }

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
            'priority' => self::PRIORITY_NORMAL,
            'routes'   => array(),
            'types' =>  [
                'digit' => '[0-9]++',
                'alnum' => '[0-9A-Za-z]++',
                'alpha' => '[A-Za-z]++',
                '*'     => '.+?',
                '**'    => '.++',
                ''      => '[^/\.]++',
            ],
        ));

        parent::_initialize($config);
    }

    /**
     * Get the priority of the resolver
     *
     * @return  integer The resolver priority
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * Add a route for matching
     *
     * @param string $route The route regex You can use multiple pre-set regex filters, like [digit:id]
     * @param string $path The path this route should point to.
     * @return ComPagesDispatcherRouterResolverInterface
     */
    public function addRoute($route, $path)
    {
        $route = trim($route, '/');
        $path  = trim($path, '/');

        if(strpos($route, '[') !== false) {
            $this->__dynamic_routes[$route] = $path;
        } else {
            $this->__static_routes[$route] = $path;
        }

        return $this;
    }

    /**
     * Add routes for matching
     *
     * @param array $routes  The routes to be added
     * @return ComPagesDispatcherRouterResolverInterface
     */
    public function addRoutes($routes)
    {
        foreach((array)KObjectConfig::unbox($routes) as $path => $routes)
        {
            foreach((array) $routes as $route)
            {
                if (is_numeric($path)) {
                    $this->addRoute($route, $route);
                } else {
                    $this->addRoute($route, $path);
                }
            }
        }

        return $this;
    }

    /**
     *  Resolve the request
     *
     * @param ComPagesDispatcherRouterInterface $router
     * @return false|KHttpUrl Returns the matched route or false if no match was found
     */
    public function resolve(ComPagesDispatcherRouterInterface $router)
    {
        $query = array();
        $match = false;

        $path = $router->getPath();

        //Check if we have a static route
        if(!isset($this->__static_routes[$path]))
        {
            //Match against the dynamic routes
            foreach($this->__dynamic_routes as $route => $target)
            {
                // Compare longest non-param string with url, if match compile route and try to match it
                if (strncmp($path.'/', $route, strpos($route, '[')) === 0)
                {
                    //Try to match
                    if (false !== $query = $this->parseRoute($route, $path))
                    {
                        $match = $this->__dynamic_routes[$route];

                        //Move matched route to the top of the stack for reverse lookups
                        $this->__dynamic_routes = array($route => $match) + $this->__dynamic_routes;
                        break;
                    }
                }
            }
        }
        else
        {
            $match = $this->__static_routes[$path];

            //Move matched route to the top of the stack for reverse lookups
            $this->__static_routes = array($path => $match) + $this->__static_routes;
        }

        return $match !== false ? $this->buildRoute($match, $query) : false;
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
        $route  = false;
        $routes = array_flip(array_reverse($this->__static_routes));

        //Check if we have a static route
        if(!isset($routes[$path]))
        {
            $routes = array_flip(array_reverse($this->__dynamic_routes));

            //Generate the dynamic route
            if(isset($routes[$path])) {
                $route = $routes[$path];
            }
        }
        else $route = $routes[$path];

        return $route !== false ? $this->buildRoute($route, $query) : false;
    }

    /**
     * Parse a route
     *
     * @param string $route The route regex You can use multiple pre-set regex filters, like [digit:id]
     * @param string $path  The path to parse
     * @return array|false
     */
    public function parseRoute($route, $path)
    {
        $result = false;

        if(strpos($route, '[') !== false)
        {
            //Compile the regex for the route
            if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER))
            {
                foreach ($matches as $match)
                {
                    list($block, $pre, $type, $param, $optional) = $match;

                    if (isset($this->_match_types[$type])) {
                        $type = $this->_match_types[$type];
                    }

                    if ($pre === '.') {
                        $pre = '\.';
                    }

                    $optional = $optional !== '' ? '?' : null;

                    //Older versions of PCRE require the 'P' in (?P<named>)
                    $pattern = '(?:'
                        . ($pre !== '' ? $pre : null)
                        . '('
                        . ($param !== '' ? "?P<$param>" : null)
                        . $type
                        . ')'
                        . $optional
                        . ')'
                        . $optional;

                    $route = str_replace($block, $pattern, $route);
                }
            }


            //Try to match
            if (preg_match("`^$route$`u", $path, $query) === 1)
            {
                foreach ((array)$query as $key => $value)
                {
                    if (is_numeric($key)) {
                        unset($query[$key]);
                    }
                }

                $result = $query;
            }
        }

        return $result;
    }

    /**
     * Build a route
     *
     * @param string $route The route regex You can use multiple pre-set regex filters, like [digit:id]
     * @param array @params Associative array of parameters to replace placeholders with.
     * @return KHttpUrl Returns the generated route
     */
    public function buildRoute($route, array $query = array())
    {
        if(strpos($route, '[') !== false)
        {
            //Prepend base path to route url again
            if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER))
            {
                foreach ($matches as $index => $match)
                {
                    list($block, $pre, $type, $param, $optional) = $match;

                    if ($pre) {
                        $block = substr($block, 1);
                    }

                    if (isset($query[$param])) {
                        //Part is found, replace for param value
                        $route = str_replace($block, $query[$param], $route);
                        unset($query[$param]);
                    } elseif ($optional && $index !== 0) {
                        //Only strip preceeding slash if it's not at the base
                        $route = str_replace($pre . $block, '', $route);
                    } else {
                        //Strip match block
                        $route = str_replace($block, '', $route);
                    }
                }
            }
        }

        //Create the route
        $route = $this->getObject('http.url', array('url' => $route))
                ->setQuery($query);

        return $route;
    }
}