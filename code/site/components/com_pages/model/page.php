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

    public function getPage($path = '')
    {
        $result = null;

        if ($path)
        {
            if ($page = $this->getObject('page.registry')->getPage($path))
            {
                $result = $this->getObject('com:pages.model.entity.page',
                    array('data'  => $page->toArray())
                );
            }
        }
        else
        {
            if($this->__page && !$this->__page instanceof ComPagesModelEntityPage)
            {
                $this->__page = $this->getObject('com:pages.model.entity.page',
                    array('data'  =>  $this->__page->toArray())
                );
            }

            $result = $this->__page;
        }

        return $result;
    }

    public function getCollection($source = '', $state = array())
    {
        $collection = false;

        if(!$source)
        {
            if($page = $this->getPage())
            {
                if(is_null($this->__collection)) {
                    $this->__collection = $this->_createCollection($this->getPage()->route);
                }

                $collection = $this->__collection;
            }
        }
        else
        {
            if(!$collection = $this->_createCollection($source, $state))
            {
                throw new UnexpectedValueException(
                    'Collection: '.$source.' does not exist'
                );
            }
        }

        return $collection;
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

    protected function _createCollection($source, $state = array())
    {
        $model = false;

        if($collection = $this->getObject('page.registry')->getCollection($source))
        {
            //Create the model
            $model = $this->getObject($collection->source);

            if(!$model instanceof KModelInterface)
            {
                throw new UnexpectedValueException(
                    'Collection: '.get_class($model).' does not implement KModelInterface'
                );
            }

            //Set the state
            if(isset($collection->state)) {
                $state  = $collection->state->merge($state);
            }

            $model->setState(KObjectConfig::unbox($state));
        }

        return $model;
    }
}