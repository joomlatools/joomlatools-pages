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
     * Set the route status.
     *
     * @param integer $status The route status value.
     * @return ComPagesDispatcherRouterRouteInterface
     */
    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    /**
     * Set the route status.
     *
     * @return integer The route status value.
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

    /**
     * Returns the query portion as a string or array
     *
     * @param   boolean      $toArray If TRUE return an array. Default FALSE
     * @param   boolean|null $escape  If TRUE escapes '&' to '&amp;' for xml compliance. If NULL use the default.
     * @return  string|array The query string; e.g., `foo=bar&baz=dib`.
     */
    public function getQuery($toArray = false, $escape = null)
    {
        $result =  parent::getQuery($toArray, $escape);

        if(!$toArray) {
            $result = str_replace(['%5B', '%5D'], ['[', ']'], $result);
        }

        return $result;
    }
}