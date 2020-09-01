<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Router Page Route
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Route
 */
class ComPagesDispatcherRouterRoutePage extends ComPagesDispatcherRouterRouteAbstract
{
    protected $__page = null;

    public function getPage()
    {
        if(is_string($this->__page)) {
            $this->__page = $this->getObject('page.registry')->getPageEntity($this->__page);
        }

        return $this->__page;
    }

    public function getState()
    {
        $state = array();

        if($page = $this->getPage())
        {
            if(($collection = $page->isCollection()) && isset($collection['state'])) {
                $state = $collection['state'];
            }
        }

        return $state;
    }

    public function setPath($path)
    {
        parent::setPath($path);

        //A resolved route can only receive a path to be resolved, do not change it
        if(!$this->isResolved() &&  $this->getObject('page.registry')->isPage($path)) {
            $this->__page = trim($this->getPath(), '/');
        }
    }

    public function setGenerated()
    {
        parent::setGenerated();

        //Remove any hardcoded states from the generated route
        $this->query = array_diff_key($this->query, $this->getState());

        return $this;
    }

    /**
     * Generate debug info
     *
     * @return array
     */
    public function __debugInfo()
    {
        $result = parent::__debugInfo();
        $result['page'] = $this->_page_path;

        return $result;
    }
}