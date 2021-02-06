<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Abstract Dispatcher Router
 *
 * The router add resolvers to a double linked list to allow. The order in which resolvers are called depends on the
 * process, when resolving resolvers are called in LIFO order, when generating the resolvers are called in FIFO order.
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router
 */
abstract class ComPagesDispatcherRouterAbstract extends KObject implements ComPagesDispatcherRouterInterface, KObjectMultiton
{
    /**
     * Request object
     *
     * @var	KControllerRequestInterface
     */
    private $__request;

    /**
     * The route resolver stack
     *
     * @var	SplDoublyLinkedList
     */
    private $__resolvers;

    /**
     * Constructor
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setRequest($config->request);

        //Create the resolver queue
        $this->__resolvers = new SplDoublyLinkedList();

        //Attach the router resolvers
        $resolvers = (array) KObjectConfig::unbox($config->resolvers);

        foreach ($resolvers as $key => $value)
        {
            if (is_numeric($key)) {
                $this->attachResolver($value);
            } else {
                $this->attachResolver($key, $value);
            }
        }
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config    An optional ObjectConfig object with configuration options.
     * @return 	void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'request'   => null,
            'route'     => 'default',
            'resolvers' => [],
        ));

        parent::_initialize($config);
    }

    /**
     * Resolve a route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface|KObjectInterface $route The route to resolve
     * @param array $parameters Route parameters
     * @return false| ComPagesDispatcherRouterInterface Returns the matched route or false if no match was found
     */
    public function resolve($route, array $parameters = array())
    {
        $route = $this->getRoute($route, $parameters);

        if(!$route->isResolved())
        {
            $this->__resolvers->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);

            foreach($this->__resolvers as $resolver) {
                $resolver->resolve($route, $parameters);
            }
        }

        return $route->isResolved() ? $route : false;
    }

    /**
     * Generate a route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface|KObjectInterface $route The route to resolve
     * @param array $parameters Route parameters
     * @return false|KHttpUrlInterface Returns the generated route
     */
    public function generate($route, array $parameters = array())
    {
        $route = $this->getRoute($route, $parameters);

        if(!$route->isGenerated())
        {
            $this->__resolvers->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP);

            foreach($this->__resolvers as $resolver) {
                $resolver->generate($route, $parameters);
            }
        }

        return $route->isGenerated() ? $route : false;
    }

    /**
     * Qualify a route
     *
     * Replace the url authority with the authority of the request url
     * @param ComPagesDispatcherRouterRouteInterface $route The route to qualify
     * @param   bool  $replace If the url is already qualified replace the authority
     * @return string
     */
    public function qualify(ComPagesDispatcherRouterRouteInterface $route,  $replace = false)
    {
        $url = clone $route;

        if($replace || !$route->isAbsolute())
        {
            $request = $this->getRequest();

            //Qualify the url
            $url->setUrl($request->getUrl()->toString(KHttpUrl::AUTHORITY));

            $base = $request->getBasePath();
            $path = trim($url->getPath(), '/');

            //Add script name (index.php) if request is not rewritten
            if(!isset($_SERVER['PAGES_PATH'])) {
                $url->setPath($base . basename($_SERVER['SCRIPT_NAME']).'/' . $path);
            } else {
                $url->setPath($base.'/'.$path);
            }
        }

        return $url;
    }

    /**
     * Get a route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface $route The route to compile
     * @param array $parameters Route parameters
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function getRoute($route, array $parameters = array())
    {
        if(!$route instanceof ComPagesDispatcherRouterRouteInterface)
        {
            $name = $this->getConfig()->route;

            if(is_string($name) && strpos($name, '.') === false )
            {
                $identifier         = $this->getIdentifier()->toArray();
                $identifier['path'] = ['dispatcher', 'router', 'route'];
                $identifier['name'] = $name;

                $identifier = $this->getIdentifier($identifier);
            }
            else $identifier = $this->getIdentifier($name);

            $route = $this->getObject($identifier, ['url' => $route, 'query' => $parameters]);

            if(!$route instanceof ComPagesDispatcherRouterRouteInterface)
            {
                throw new UnexpectedValueException(
                    'Route: '.get_class($route).' does not implement ComPagesDispatcherRouterRouteInterface'
                );
            }
        }
        else
        {
            $route = clone $route;
            $route->setQuery($parameters, true);
        }

        return $route;
    }

    /**
     * Get a route resolver
     *
     * @param   mixed   $resolver  KObjectIdentifier object or valid identifier string
     * @param   array $config  An optional associative array of configuration settings
     * @throws UnexpectedValueException
     * @return  ComPagesDispatcherRouterResolverInterface
     */
    public function getResolver($resolver, $config = array())
    {
        if(is_string($resolver) && strpos($resolver, '.') === false )
        {
            $identifier         = $this->getIdentifier()->toArray();
            $identifier['path'] = ['dispatcher', 'router', 'resolver'];
            $identifier['name'] = $resolver;

            $identifier = $this->getIdentifier($identifier);
        }
        else $identifier = $this->getIdentifier($resolver);

        $resolver = $this->getObject($identifier, $config);

        if (!($resolver instanceof ComPagesDispatcherRouterResolverInterface))
        {
            throw new UnexpectedValueException(
                "Resolver $identifier does not implement ComPagesDispatcherRouterResolverInterface"
            );
        }

        return $resolver;
    }

    /**
     * Get the list of attached resolvers
     *
     * @return array
     */
    public function getResolvers()
    {
        return iterator_to_array($this->__resolvers);
    }

    /**
     * Attach a route resolver
     *
     * @param   mixed  $resolver An object that implements ObjectInterface, ObjectIdentifier object
     *                            or valid identifier string
     * @param   array $config  An optional associative array of configuration settings
     * @param  bool $prepend If true, the resolver will be prepended instead of appended.
     * @return  ComPagesDispatcherRouterAbstract
     */
    public function attachResolver($resolver, $config = array(), $prepend = false)
    {
        if (!($resolver instanceof ComPagesDispatcherRouterResolverInterface)) {
            $resolver = $this->getResolver($resolver, $config);
        }

        //Enqueue the router resolver
        if($prepend) {
            $this->__resolvers->unshift($resolver);
        } else {
            $this->__resolvers->push($resolver);
        }

        return $this;
    }

    /**
     * Set the request object
     *
     * @param KControllerRequestInterface $request A request object
     * @return ComPagesDispatcherRouterAbstract
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
     * Deep clone of this instance
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        $this->__request   = clone $this->__request;
        $this->__resolvers = clone $this->__resolvers;
    }
}