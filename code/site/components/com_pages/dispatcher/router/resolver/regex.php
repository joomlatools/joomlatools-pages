<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */


/**
 * Dispatcher Regex Route Resolver
 *
 * Inspired by Altorouter: https://github.com/dannyvankooten/AltoRouter
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Resolver
 */
class ComPagesDispatcherRouterResolverRegex  extends ComPagesDispatcherRouterResolverAbstract
{
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
            'routes'   => array(),
            'types' =>  [
                'month' => '(0?[1-9]|1[012])',
                'year'  => '(19|20)\d{2}',
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
     * Add a route for matching
     *
     * @param string $regex The route regex You can use multiple pre-set regex filters, like [digit:id]
     * @param string $path The path this route should point to.
     * @return ComPagesDispatcherRouterResolverInterface
     */
    public function addRoute($regex, $path)
    {
        $regex = trim($regex, '/');
        $path  = rtrim($path, '/');

        if(strpos($regex, '[') !== false) {
            $this->__dynamic_routes[$regex] = $path;
        } else {
            $this->__static_routes[$regex] = $path;
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
            foreach((array) $routes as $regex)
            {
                if (is_numeric($path)) {
                    $this->addRoute($regex, $regex);
                } else {
                    $this->addRoute($regex, $path);
                }
            }
        }

        return $this;
    }

    /**
     *  Resolve the route
     *
     * @param ComPagesDispatcherRouterInterface $route The route to resolve
     * @return bool
     */
    public function resolve(ComPagesDispatcherRouterRouteInterface $route)
    {
        $result = false;
        $path  = ltrim($route->getPath(), '/');

        //Check if we have a static route
        if(!isset($this->__static_routes[$path]))
        {
            //Sort routes longest path to shortest
            arsort($this->__dynamic_routes);

            //Match against the dynamic routes
            foreach($this->__dynamic_routes as $regex => $target)
            {
                //Compare longest non-param string with path, if not match continue
                //$pos = strpos($route, '/[') ?? strpos($route, '.[');
                //if (substr($route, 0, $pos) != substr($path, 0, $pos)) {
                //    continue;
                //}

                //Try to parse the route
                if (false !== $this->_parseRoute($regex, $route))
                {
                    $result = $this->__dynamic_routes[$regex];

                    //Move matched route to the top of the stack for reverse lookups
                    $this->__dynamic_routes = array($regex => $result) + $this->__dynamic_routes;
                    break;
                }
            }
        }
        else
        {
            $result = $this->__static_routes[$path];

            //Move matched route to the top of the stack for reverse lookups
            $this->__static_routes = array($path => $result) + $this->__static_routes;
        }

        if($result !== false) {
            $this->_buildRoute($result, $route);
        }

        return $result !== false ? parent::resolve($route) : false;
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a route. Replace regexes with supplied parameters
     *
     * @param ComPagesDispatcherRouterInterface $route The route to generate
     * @return bool
     */
    public function generate(ComPagesDispatcherRouterRouteInterface $route)
    {
        $result = false;
        $path   = ltrim($route->getPath(), '/');

        $routes = array_flip(array_reverse($this->__dynamic_routes));

        //Check if we have a static route
        if(!isset($routes[$path]))
        {
            $routes = array_flip(array_reverse($this->__static_routes, true));

            //Generate the dynamic route
            if(isset($routes[$path])) {
                $result = $routes[$path];
            }
        }
        else $result = $routes[$path];

        if($result !== false) {
            $this->_buildRoute($result, $route);
        }

        return $result !== false ? parent::generate($route) : false;
    }

    /**
     * Parse a route
     *
     * @param string $regex The route regex You can use multiple pre-set regex filters, like [digit:id]
     * @param ComPagesDispatcherRouterInterface $route The route to parse
     * @return bool
     */
    protected function _parseRoute($regex, ComPagesDispatcherRouterRouteInterface $route)
    {
        $result = false;

        $query  = array();
        $path   = ltrim($route->getPath(), '/');

        if(strpos($regex, '[') !== false)
        {
            //Compile the regex for the route
            if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $regex, $matches, PREG_SET_ORDER))
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

                    $regex = str_replace($block, $pattern, $regex);
                }
            }


            //Try to match
            if (preg_match("`^$regex$`u", $path, $query) === 1)
            {
                foreach ((array)$query as $key => $value)
                {
                    if (is_numeric($key)) {
                        unset($query[$key]);
                    }
                }

                $route->setQuery($query, true);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Build a route
     *
     * @param string $route The route regex You can use multiple pre-set regex filters, like [digit:id]
     * @param ComPagesDispatcherRouterInterface $route The route to build
     * @return bool
     */
    protected function _buildRoute($regex, ComPagesDispatcherRouterRouteInterface $route)
    {
        $regex = ltrim($regex, '/');

        if(strpos($regex, '[') !== false)
        {
            //Prepend base path to route url again
            if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $regex, $matches, PREG_SET_ORDER))
            {
                foreach ($matches as $index => $match)
                {
                    list($block, $pre, $type, $param, $optional) = $match;

                    if ($pre) {
                        $block = substr($block, 1);
                    }

                    if (isset($route->query[$param])) {
                        //Part is found, replace for param value
                        $regex = str_replace($block, $route->query[$param], $regex);
                        unset($route->query[$param]);
                    } elseif ($optional) {
                        //Only strip preceeding slash if it's not at the base
                        $regex = str_replace($pre . $block, '', $regex);
                    } else {
                        //Strip match block
                        $regex = str_replace($block, '', $regex);
                    }
                }
            }
        }

        return $route->setPath('/'.ltrim($regex, '/'));
    }
}