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
     * Response object
     *
     * @var	KControllerResponseInterface
     */
    private $__response;

    /**
     * The resolver queue
     *
     * @var	KObjectQueue
     */
    private $__queue;

    /**
     * List of router resolvers
     *
     * @var array
     */
    protected $__resolvers;

    /**
     * The canonical url
     *
     * @var KHttpUrl
     */
    protected $_canonical;

    /**
     * The resolved rotue
     *
     * @var false|KHttpUrl
     */
    protected $_resolved_route;

    /**
     * Constructor
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setResponse($config->response);

        //Create the resolver queue
        $this->__queue = $this->getObject('lib:object.queue');

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
            'response'   => null,
            'resolvers'  => array('redirect', 'http'),
        ));

        parent::_initialize($config);
    }

    /**
     * Set the response object
     *
     * @param KControllerResponseInterface $response A response object
     * @return ComPagesDispatcherRouterInterface
     */
    public function setResponse(KControllerResponseInterface $response)
    {
        $this->__response = $response;
        return $this;
    }

    /**
     * Get the response object
     *
     * @return KControllerResponseInterface
     */
    public function getResponse()
    {
        return $this->__response;
    }

    /**
     * Get the request path
     *
     * @return string
     */
    public function getPath()
    {
        $base = $this->getResponse()->getRequest()->getBasePath();
        $url  = $this->getResponse()->getRequest()->getUrl()->getPath();

        return trim(str_replace(array($base, '/index.php'), '', $url), '/');
    }

    /**
     * Get the canonical url
     *
     *  If no canonical url is set return the request url
     *
     * @return  KHttpUrl|null  A HttpUrl object or NULL if no canonical url could be found
     */
    public function getCanonicalUrl()
    {
        if(!$this->_canonical) {
            $url = $this->getResponse()->getRequest()->getUrl();
        } else {
            $url = $this->_canonical;
        }

        return $url;
    }

    /**
     * Sets the canonical url
     *
     * @param  string|KHttpUrlInterface $canonical
     * @return ComPagesDispatcherRouterInterface
     */
    public function setCanonicalUrl($canonical)
    {
        if(!($canonical instanceof KHttpUrlInterface)) {
            $canonical = $this->getObject('lib:http.url', array('url' => $canonical));
        }

        $this->getResponse()->getHeaders()->set('Link', array((string) $canonical => array('rel' => 'canonical')));
        $this->_canonical = $canonical;

        return $this;
    }

    /**
     * Qualify a url
     *
     * Replace the url authority with the authority of the request url
     *
     * @param KHttpUrl $url The url to qualify
     * @param bool $replace If the url is already qualified replace the authority
     * @return KHttpUrl
     */
    public function qualifyUrl(KHttpUrl $url, $force = false)
    {
        if($force || !$url->toString(KHttpUrl::AUTHORITY))
        {
            $request = $this->getResponse()->getRequest();

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
        }

        return $url;
    }

    /**
     * Resolve the request
     *
     * Iterate through the router resolvers. If a resolver returns not FALSE the chain will be stopped.
     *
     * @return false|KHttpUrl Returns the matched route or false if no match was found
     */
    public function resolve()
    {
        if(!isset($this->_resolved_route))
        {
            $this->_resolved_route = false;

            foreach($this->__queue as $resolver)
            {
                if($resolver instanceof ComPagesDispatcherRouterResolverInterface)
                {
                    if(false !== $result = $resolver->resolve($this))
                    {
                        $this->_resolved_route = $result;
                        break;
                    }
                }
            }
        }

        return $this->_resolved_route;
    }

    /**
     * Generate a route
     *
     * Iterate through the router resolvers. If a resolver returns not FALSE the chain will be stopped.
     *
     * @param string $path The path to generate a route for
     * @param array @params Associative array of parameters to replace placeholders with.
     * @return false|KHttpUrl Returns the generated route
     */
    public function generate($path, array $query = array())
    {
        $route = false;
        foreach($this->__queue as $resolver)
        {
            if($resolver instanceof ComPagesDispatcherRouterResolverInterface)
            {
                if(false !== $route = $resolver->generate($path, $query, $this))
                {
                    $route = $this->qualifyUrl($route, true);
                    break;
                }
            }
        }

        return $route;
    }

    /**
     * Get a resolver handler by identifier
     *
     * @param   mixed $resolver An object that implements ObjectInterface, ObjectIdentifier object
     *                                 or valid identifier string
     * @param   array $config An optional associative array of configuration settings
     * @throws UnexpectedValueException
     * @return ComPagesDispatcherRouterInterface
     */
    public function getResolver($resolver, $config = array())
    {
        //Create the complete identifier if a partial identifier was passed
        if (is_string($resolver) && strpos($resolver, '.') === false)
        {
            $identifier = $this->getIdentifier()->toArray();

            if($identifier['package'] != 'dispatcher') {
                $identifier['path'] = array('dispatcher', 'router', 'resolver');
            } else {
                $identifier['path'] = array('router', 'resolver');
            }

            $identifier['name'] = $resolver;
            $identifier = $this->getIdentifier($identifier);
        }
        else $identifier = $this->getIdentifier($resolver);

        if (!isset($this->__resolvers[$identifier->name]))
        {
            $resolver = $this->getObject($identifier, array_merge($config, array('response' => $this)));

            if (!($resolver instanceof ComPagesDispatcherRouterResolverInterface))
            {
                throw new UnexpectedValueException(
                    "Resolver $identifier does not implement DispatcherRouterResolverInterface"
                );
            }

            $this->__resolvers[$resolver->getIdentifier()->name] = $resolver;
        }
        else $resolver = $this->__resolvers[$identifier->name];

        return $resolver;
    }

    /**
     * Attach a router resolver
     *
     * @param   mixed  $resolver An object that implements ObjectInterface, ObjectIdentifier object
     *                            or valid identifier string
     * @param   array $config  An optional associative array of configuration settings
     * @return ComPagesDispatcherRouterInterface
     */
    public function attachResolver($resolver, $config = array())
    {
        if (!($resolver instanceof ComPagesDispatcherRouterResolverInterface)) {
            $resolver = $this->getResolver($resolver, $config);
        }

        //Enqueue the resolver
        $this->__queue->enqueue($resolver, $resolver->getPriority());

        return $this;
    }

    /**
     * Deep clone of this instance
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        $this->__queue    = clone $this->__queue;
        $this->__response = clone $this->__response;
    }

}