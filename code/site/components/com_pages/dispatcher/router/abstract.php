<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

abstract class ComPagesDispatcherRouterAbstract extends KDispatcherRouter
{
    /**
     * Static page routes
     *
     * @var array
     */
    private $__static_routes = array();

    /**
     * Dynamic page routes
     *
     * @var array
     */
    private $__dynamic_routes = array();

    /**
     * Request object
     *
     * @var	KControllerRequestInterface
     */
    private $__request;

    /**
     * Array of default match types (regex helpers)
     *
     * @var array
     */
    protected $_match_types;

    /**
     * The matched page
     *
     * @var KHttpUrl
     */
    protected $_matched_route;

    /**
     * Constructor
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setRequest($config->request);

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
            'request' => null,
            'routes'  => array('' => 'index'),
            'types'   =>  [
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
     * Set the request object
     *
     * @param KControllerRequestInterface $request A request object
     * @return KDispatcherRouterInterface
     */
    public function setRequest(KControllerRequestInterface $request)
    {
        $this->__request = $request;
        return $this;
    }

    /**
     * Get the request object
     *
     * @return KControllerRequestInterface
     */
    public function getRequest()
    {
        return $this->__request;
    }

    /**
     * Add a route for matching
     *
     * If only a page path is defined the route is considered a static route
     *
     * @param string $page  The page path where this route should point to.
     * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [digit:id]
     * @return KDispatcherRouterInterface
     */
    public function addRoute($page, $route)
    {
        if(strpos($route, '[') !== false) {
            $this->__dynamic_routes[$page] = $route;
        } else {
            $this->__static_routes[$page] = $route;
        }

        return $this;
    }

    /**
     * Add routes for matching
     *
     * @param array $routes  The routes to be added
     * @return KDispatcherRouterInterface
     */
    public function addRoutes($routes)
    {
        foreach((array)KObjectConfig::unbox($routes) as $key => $value)
        {
            if (is_numeric($key)) {
                $this->addRoute($value, $value);
            } else {
                $this->addRoute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Match the request
     *
     * @return false|KHttpUrl Returns the matched route or false if no match was found
     */
    public function match()
    {
        if(!isset($this->_matched_route))
        {
            $query = array();
            $match = false;

            //Get the request path
            $base = $this->getRequest()->getBasePath();
            $url  = $this->getRequest()->getUrl()->getPath();

            $source = trim(str_replace(array($base, '/index.php'), '', $url), '/');

            //Check if we have a static route
            if(!isset($this->__static_routes[$source]))
            {
                foreach($this->__dynamic_routes as $page => $route)
                {
                    // Compare longest non-param string with url, if match compile route and try to match it
                    if (strncmp($source.'/', $route, strpos($route, '[')) === 0)
                    {
                        //Compile the regex for the route
                        $regex = $this->__compileRoute($route);

                        //Try to match
                        if (preg_match($regex, $source, $query) === 1)
                        {
                            //Set the matched params in the request query
                            foreach((array) $query as $key => $value)
                            {
                                if(!is_numeric($key)) {
                                    $this->getRequest()->query->set($key, $value);
                                } else {
                                    unset($query[$key]);
                                }
                            }

                            $match = $page;
                            break;
                        }
                    }
                }
            }
            else $match = $this->__static_routes[$source];

            //Create the match
            $this->_matched_route = $this->getObject('http.url')
                ->setQuery($query)
                ->setPath($match);
        }

        return $this->_matched_route;
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a route. Replace regexes with supplied parameters
     *
     * @param string $path The path to generate a route for
     * @param array @params Associative array of parameters to replace placeholders with.
     * @return KHttpUrl Returns the generated route
     */
    public function generate($path, array $query = array(), $escape = true)
    {
        //Generate the path
        if(isset($this->__dynamic_routes[$path]))
        {
            $route = $this->__dynamic_routes[$path];

            //Prepend base path to route url again
            if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER))
            {
                foreach($matches as $index => $match)
                {
                    list($block, $pre, $type, $param, $optional) = $match;

                    if ($pre) {
                        $block = substr($block, 1);
                    }

                    if(isset($query[$param]))
                    {
                        //Part is found, replace for param value
                        $route = str_replace($block, $query[$param], $route);
                        unset($query[$param]);
                    }
                    elseif ($optional && $index !== 0)
                    {
                        //Only strip preceeding slash if it's not at the base
                        $route = str_replace($pre . $block, '', $route);
                    }
                    else
                    {
                        //Strip match block
                        $route = str_replace($block, '', $route);
                    }
                }
            }

            $path = $route;
        }

        //Create the route
        $route = $this->getObject('http.url', array('escape' => $escape))->setQuery($query);

        $base = $this->getRequest()->getBasePath();
        $url  = $this->getRequest()->getUrl();
        if(strpos($url->getPath(), 'index.php') !== false) {
            $route->setPath($base.'/index.php/'.$path);
        } else {
            $route->setPath($base.'/'.$path);
        }

        //Automatically quality the route
        if($this->getRequest()->getFormat() != 'html')
        {
            $route->scheme = $url->scheme;
            $route->host   = $url->host;
            $route->port   = $url->port;
        }

        return $route;
    }

    /**
     * Compile the regex for a given route
     *
     * @param  string $route
     * @return string
     */
    private function __compileRoute($route)
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
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

        return "`^$route$`u";
    }

    /**
     * Deep clone of this instance
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        $this->__request = clone $this->__request;
    }

}