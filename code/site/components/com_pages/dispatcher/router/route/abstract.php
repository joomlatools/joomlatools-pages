<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Abstract Router Route
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Route
 */
abstract class ComPagesDispatcherRouterRouteAbstract extends KHttpUrl implements ComPagesDispatcherRouterRouteInterface
{
    /**
     * The status
     *
     * Available entity status values are defined as STATUS_ constants
     *
     * @var string
     */
    protected $_status = null;

    /**
     * The initial route
     *
     * @var ComPagesDispatcherRouterRouteInterface
     */
    protected $_initial_route = null;

    /**
     * Constructor
     *
     * @param KObjectConfig $config  An optional KObjectConfig object with configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Set the url
        $this->setQuery(KObjectConfig::unbox($config->query), true);

        //Store the initial state
        $this->_initial_route = clone $this;
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation
     *
     * @param   KObjectConfig $config  An optional KObjectConfig object with configuration options
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'query'  => array(),
        ));

        parent::_initialize($config);
    }

    /**
     * Set the status.
     *
     * @param integer $status The status value.
     */
    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    /**
     * Get the status.
     *
     * @return integer The status value.
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Test if the route has been resolved
     *
     * @return	bool
     */
    public function isResolved()
    {
        return (bool) $this->_status === self::STATUS_RESOLVED;
    }

    /**
     * Test if the route has been generated
     *
     * @return	bool
     */
    public function isGenerated()
    {
        return (bool) $this->_status === self::STATUS_GENERATED;
    }
}