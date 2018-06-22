<?php
/**
 * Joomlatools Framework Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelBehaviorRecursable extends KModelBehaviorAbstract
{
    private $__pages;
    private $__children;

    public function hasChildren()
    {
        $result = false;
        $key    = $this->getIdentityKey();

        if(isset($this->__children[$this->$key])) {
            $result = (boolean) count($this->__children[$this->$key]);
        }

        return $result;
    }

    public function getChildren()
    {
        $result = array();

        if($this->hasChildren())
        {
            $key    = $this->getIdentityKey();
            $parent = $this->$key;

            $result = $this->__children[$parent];
        }

        return $result;
    }

    public function getRecursiveIterator($max_level = 0, $parent = null)
    {
        if($parent && !$this->__pages->find($parent)) {
            throw new \OutOfBoundsException('Parent does not exist');
        } else {
            $parent = '.';
        }

        //If the parent doesn't have any children create an empty rowset
        if(isset($this->__children[$parent])) {
            $pages = $this->__children[$parent];
        } else {
            $pages = array();
        }

        return new ComPagesModelIteratorRecursive($pages, $max_level);
    }

    public function getMixableMethods($exclude = array())
    {
        $exclude = array_merge($exclude, array('getNodes'));
        $methods = parent::getMixableMethods($exclude);

        return $methods;
    }

    public function isSupported()
    {
        $page = $this->getMixer();

        if($page instanceof KModelEntityInterface)
        {
            if(!$page->hasProperty('path'))  {
                return false;
            }
        }

        return true;
    }

    protected function _afterFetch(KModelContext $context)
    {
        $pages = KObjectConfig::unbox($context->entity);

        if ($pages instanceof KModelEntityComposable)
        {
            //Store the pages
            $this->__pages  = $pages;
            $pages->mixin($this);

            $this->__iterator = null;
            $this->__children = array();

            foreach ($this->__pages as $key => $page)
            {
                //Force mixin the behavior into each page
                $page->mixin($this);

                if($page->isRecursable()) {
                    $parent = dirname($page->path);
                } else {
                    $parent = '.';
                }

                //Store the nodes by parent
                $this->__children[$parent][$key] = $page;
            }

            //Sort the children
            ksort($this->__children);
        }
    }
}