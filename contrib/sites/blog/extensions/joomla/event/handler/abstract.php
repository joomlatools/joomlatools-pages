<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ExtJoomlaEventHandlerAbstract extends JEvent implements ExtJoomlaEventHandlerInterface
{
    /**
     * The object identifier
     *
     * @var KObjectIdentifier
     */
    private $__object_identifier;

    /**
     * The object manager
     *
     * @var KObjectManager
     */
    private $__object_manager;

    /**
     * The object configuration.
     *
     * @var KObjectConfig
     */
    private $__object_config;

    /**
     * Constructor.
     *
     * @param   object  $dispatcher Event dispatcher
     * @param   array|  $config     Configuration options
     */
    public function __construct($dispatcher, $config = array())
    {
        $config = new KObjectConfig($config);

        $this->_initialize($config);

        // Set the object config.
        $this->__object_config = $config;

        // Do not call the parent constructor override it and implement logic ourselves.
        parent::__construct($dispatcher);

        $classname = KStringInflector::underscore(get_class($this));
        $parts     = KStringInflector::explode($classname);

        $identifier = 'ext:'.$parts[1].'.event.handler.'.$parts[4];

        //Inject the identifier
        $this->__object_identifier = KObjectManager::getInstance()->getIdentifier($identifier);

        //Inject the object manager
        $this->__object_manager = KObjectManager::getInstance();
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config Configuration options.
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {

    }

    /**
     * Get the object configuration
     *
     * If no identifier is passed the object config of this object will be returned. Function recursively
     * resolves identifier aliases and returns the aliased identifier.
     *
     * @param  mixed $identifier An ObjectIdentifier, identifier string or object implementing ObjectInterface
     * @return KObjectConfig
     */
    public function getConfig($identifier = null)
    {
        if (isset($identifier)) {
            $result = $this->__object_manager->getIdentifier($identifier)->getConfig();
        } else {
            $result = $this->__object_config;
        }

        return $result;
    }

    /**
     * Get an instance of an object identifier
     *
     * @param KObjectIdentifier|string $identifier An ObjectIdentifier or valid identifier string
     * @param array                    $config     An optional associative array of configuration settings.
     * @return KObjectInterface  Return object on success, throws exception on failure.
     */
    final public function getObject($identifier, array $config = array())
    {
        $result = $this->__object_manager->getObject($identifier, $config);
        return $result;
    }

    /**
     * Gets the service identifier.
     *
     * If no identifier is passed the object identifier of this object will be returned. Function recursively
     * resolves identifier aliases and returns the aliased identifier.
     *
     * @param   string|object    $identifier The class identifier or identifier object
     * @return  KObjectIdentifier
     */
    final public function getIdentifier($identifier = null)
    {
        if (isset($identifier)) {
            $result = $this->__object_manager->getIdentifier($identifier);
        } else {
            $result = $this->__object_identifier;
        }

        return $result;
    }
}