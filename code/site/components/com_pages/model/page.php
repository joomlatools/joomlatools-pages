<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelPage extends KModelAbstract
{
    private $__page;
    private $__collection;

    public function setPage(ComPagesPageObject $page, array $state = array())
    {
        $this->reset();

        $this->__page       = $page;
        $this->__collection = null;

        $this->setState($state);

        return $this;
    }

    public function getPage()
    {
        if($this->__page && !$this->__page instanceof ComPagesModelEntityPage)
        {
            $this->__page = $this->getObject('com:pages.model.entity.page',
                array('data'  =>  $this->__page->toArray())
            );
        }

        return  $this->__page;
    }

    public function getCollection()
    {
        if(is_null($this->__collection) && $page = $this->getPage())
        {
            $this->__collection = $this->getObject('com:pages.model.factory')
                    ->createCollection($this->getPage()->route);
        }

        return  $this->__collection;
    }

    public function setState(array $values)
    {
        if($collection = $this->getCollection()) {
            $collection->setState($values);
        } else {
            parent::setState($values);
        }

        return $this;
    }

    public function getState()
    {
        if($collection = $this->getCollection()) {
            $result = $collection->getState();
        } else {
            $result = parent::getState();
        }

        return $result;
    }

    protected function _actionFetch(KModelContext $context)
    {
        if($collection = $this->getCollection()) {
            $result = $collection->fetch($context);
        } else {
            $result = parent::_actionFetch($context);
        }

        return $result;
    }

    protected function _actionCount(KModelContext $context)
    {
        if($collection = $this->getCollection()) {
            $result = $collection->count($context);
        } else {
            $result = parent::_actionCount($context);
        }

        return $result;
    }

    protected function _actionReset(KModelContext $context)
    {
        if($collection = $this->getCollection()) {
            $collection->reset($context);
        } else {
            parent::_actionReset($context);
        }
    }
}