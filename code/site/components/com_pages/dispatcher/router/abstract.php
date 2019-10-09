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
     * List of router resolvers
     *
     * @var array
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
            'request' => null,
        ));

        parent::_initialize($config);
    }

    /**
     * Compile a route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface $route The route to compile
     * @param array $parameters Route parameters
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function compile($route, array $parameters = array())
    {
        if(!$route instanceof ComPagesDispatcherRouterRouteInterface) {
            $route = $this->getObject('com://site/pages.dispatcher.router.route', ['url' => $route]);
        } else {
            $route = clone $route;
        }

        return $route->setQuery($parameters, true);
    }

    /**
     *  Resolve the route
     *
     * @param string|ComPagesDispatcherRouterRouteInterface|KObjectInterface $route The route to resolve
     * @return false| ComPagesDispatcherRouterInterface Returns the matched route or false if no match was found
     */
    public function resolve($route)
    {
        $route = $this->compile($route);

        if(!$resolver = $this->getResolver($route)) {
            throw new RuntimeException('Cannot resolve route');
        }

        return $resolver->resolve($route);
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
        $route = $this->compile($route, $parameters);

        if(!$resolver = $this->getResolver($route)) {
            throw new RuntimeException('Cannot generate route');
        }

        return $resolver->generate($route, $parameters);
    }

    /**
     * Generate a url from a route
     *
     * Replace the route authority with the authority of the request url
     *
     * @param bool $replace If the url is already qualified replace the authority
     * @param   boolean      $fqr    If TRUE create a fully qualified url. Defaults to TRUE.
     * @param   boolean      $escape If TRUE escapes the url for xml compliance. Defaults to FALSE.
     * @return  string
     */
    public function qualify(ComPagesDispatcherRouterRouteInterface $route, $fqr = true, $escape = false)
    {
        $url = clone $route;
        $request = $this->getRequest();

        //Qualify the url
        $url->setUrl($request->getUrl()->toString(KHttpUrl::AUTHORITY));

        //Add index.php
        $base = $request->getBasePath();
        $path = trim($url->getPath(), '/');

        if(strpos($request->getUrl()->getPath(), 'index.php') !== false) {
            $url->setPath($base . '/index.php/' . $path);
        } else {
            $url->setPath($base.'/'.$path);
        }

        return $url->toString($fqr ? KHttpUrl::FULL : KHttpUrl::PATH + KHttpUrl::QUERY +  KHttpUrl::FRAGMENT);
    }

    /**
     * Set the request object
     *
     * @param KControllerRequestInterface $request A request object
     * @return ComPagesDispatcherRouterInterface
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
     * Get a resolver based on the route
     *
     * @param ComPagesDispatcherRouterRouteInterface $route The route to resolve
     * @throws UnexpectedValueException
     * @return false|ComPagesDispatcherRouterInterface
     */
    public function getResolver($route)
    {
        $resolver = false;

        if($route instanceof ComPagesDispatcherRouterRouteInterface)
        {
            $identifier = $route->getIdentifier()->toArray();

            if($identifier['package'] != 'dispatcher') {
                $identifier['path'] = array('dispatcher', 'router', 'resolver');
            } else {
                $identifier['path'] = array('router', 'resolver');
            }

            $identifier['package'] = $route->getScheme();
            $identifier['name']    = $route->getHost();

            $identifier = $this->getIdentifier($identifier);

            if (!isset($this->__resolvers[$identifier->name]))
            {
                $resolver = $this->getObject($identifier);

                if (!($resolver instanceof ComPagesDispatcherRouterResolverInterface))
                {
                    throw new UnexpectedValueException(
                        "Resolver $identifier does not implement DispatcherRouterResolverInterface"
                    );
                }

                $this->__resolvers[$resolver->getIdentifier()->name] = $resolver;
            }
            else $resolver = $this->__resolvers[$identifier->name];
        }

        return $resolver;
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