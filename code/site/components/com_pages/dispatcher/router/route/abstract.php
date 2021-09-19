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
     * @var integer
     */
    protected $__status = null;

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
     * Get the route state
     *
     * @return array
     */
    public function getState()
    {
        return array();
    }

    /**
     * Get the format
     *
     * @return string
     */
    public function getFormat()
    {
        return pathinfo($this->getPath(), PATHINFO_EXTENSION);
    }

    /**
     * Mark the route as resolved
     *
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function setResolved()
    {
        $this->__status = self::STATUS_RESOLVED;
        return $this;
    }

    /**
     * Mark the route as generated
     *
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function setGenerated()
    {
        $this->__status = self::STATUS_GENERATED;
        return $this;
    }

    /**
     * Test if the route has been resolved
     *
     * @return	bool
     */
    public function isResolved()
    {
        return (bool) ($this->__status == self::STATUS_RESOLVED);
    }

    /**
     * Test if the route has been generated
     *
     * @return	bool
     */
    public function isGenerated()
    {
        return (bool) ($this->__status == self::STATUS_GENERATED);
    }

    /**
     * Test if the route is absolute
     *
     * @return	bool
     */
    public function isAbsolute()
    {
        return (bool) ($this->scheme && $this->host);
    }
}