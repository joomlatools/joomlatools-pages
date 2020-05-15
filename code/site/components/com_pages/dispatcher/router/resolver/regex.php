<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */


/**
 * Regex Dispatcher Route Resolver
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
                'email' => '\S+@\S+',
                'month' => '(0?[1-9]|1[012])',
                'year'  => '(19|20)\d{2}',
                'digit' => '[0-9]++',
                '*digit' => '[0-9]+(,[0-9]+)*',
                'alnum' => '[0-9A-Za-z]++',
                '*alnum' => '[0-9A-Za-z]+(,[0-9A-Za-z]+)*',
                'alpha' => '[A-Za-z]++',
                '*alpha' => '[A-Za-z]+(,[A-Za-z]+)*',
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
     * @param string|callable $target The target this route points to
     * @return ComPagesDispatcherRouterResolverInterface
     */
    public function addRoute($regex, $target)
    {
        $regex = trim($regex, '/');

        if(is_string($target)) {
            $path = rtrim($target, '/');
        }

        if(strpos($regex, '[') !== false) {
            $this->__dynamic_routes[$regex] = $target;
        } else {
            $this->__static_routes[$regex] = $target;
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
        foreach((array)KObjectConfig::unbox($routes) as $regex => $target) {
            $this->addRoute($regex, $target);
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
            //Match against the dynamic routes
            foreach($this->__dynamic_routes as $regex => $target)
            {
                //If regex doesn't start at offset 0 compare longest non-param string with path
                if($regex[0] !== '[')
                {
                    $pos = strpos($regex, '/[') ?? strpos($regex, '.[');

                    if (substr_compare($path, $regex, 0, $pos) !== 0) {
                        continue;
                    }
                }

                //Try to parse the route
                if (false !== $this->_parseRoute($regex, $route))
                {
                    $result = $this->__dynamic_routes[$regex];
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

        if($result !== false)
        {
            if(is_callable($result)) {
                $result = (bool) call_user_func($result, $route, false);
            } else {
                $result = $this->_buildRoute($result, $route);
            }
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
        $generated = false;
        $path      = ltrim($route->getPath(), '/');

        //Dynamic routes
        $routes = $this->__dynamic_routes;

        foreach($routes as $regex => $target)
        {
            if(is_callable($target))
            {
                //Parse the route to match it
                if($this->_parseRoute($regex, $route) && (bool) call_user_func($target, $route, false) == true) {
                    $generated = true; break;
                }
            }
            else
            {
                if($target == $path && $this->_buildRoute($regex, $route)) {
                    $generated = true; break;
                }
            }
        }

        //Static routes
        if(!$generated)
        {
            $routes = array_reverse($this->__static_routes, true);

            foreach($routes as $regex => $target)
            {
                if(is_callable($target))
                {
                    //Compare the path to match it
                    if($regex == $path && (bool) call_user_func($target, $route, false) == true) {
                        $generated = true; break;
                    }
                }
                else
                {
                    if($target == $path && $this->_buildRoute($regex, $route)) {
                        $generated = true; break;
                    }
                }
            }
        }

        return $generated ? parent::generate($route) : false;
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
                    if (!is_numeric($key))
                    {
                        if(strpos($value, ',') !== false) {
                            $query[$key] = explode(',', $value);
                        }
                    }
                    else unset($query[$key]);
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
     * @return false
     */
    protected function _buildRoute($regex, ComPagesDispatcherRouterRouteInterface $route)
    {
        $result   = true;
        $replaced = array();
        $regex    = ltrim($regex, '/');

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

                    if(isset($route->query[$param]))
                    {
                        if(is_array($route->query[$param])) {
                            $value= implode(',', $route->query[$param]);
                        } else {
                            $value = $route->query[$param];
                        }

                        //Part is found, replace for param value
                        $regex = str_replace($block, $value, $regex);

                        //Store replaced param
                        $replaced[] = $param;
                    }
                    else
                    {
                        //Only strip preceeding slash if it's not at the base
                        if($optional) {
                            $regex = str_replace($pre . $block, '', $regex);
                        } else {
                            $result = false; break;
                        }
                    }
                }
            }
        }

        //Only update the route if it was build successfully
        if($result !== false)
        {
            foreach($replaced as $param) {
                unset($route->query[$param]);
            }

            if(strpos($regex, '://') === false) {
                $route->setPath('/'.ltrim($regex, '/'));
            } else {
                $route->setUrl($regex);
            }
        }

        return $result;
    }
}