<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorRecursable extends KModelBehaviorAbstract
{
    private $__parents = array();
    private $__children = array();

    protected $_level;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'priority'   => self::PRIORITY_HIGH,
        ]);

        parent::_initialize($config);
    }

    public function getParent()
    {
        return $this->__pages->find($this->path);
    }

    public function hasChildren()
    {
        $result = false;
        $parent = $this->getMixer()->getHandle();

        if(isset($this->__children[$parent])) {
            $result = (boolean) count($this->__children[$parent]);
        }

        return $result;
    }

    public function getChildren()
    {
        $result = array();

        if($this->hasChildren())
        {
            $parent = $parent = $this->getMixer()->getHandle();
            $result = $this->__children[$parent];
        }

        return $result;
    }

    public function isRoot()
    {
        return $this->getParent() === null;
    }

    public function isChild()
    {
        return $this->getParent() !== null;
    }

    public function getDirectories()
    {
        return new ComPagesModelIteratorRecursive($this->__parents, ComPagesModelIteratorRecursive::PAGES_STRUCTURE, $this->_level);
    }

    public function getFiles()
    {
        return new ComPagesModelIteratorRecursive($this->__parents, ComPagesModelIteratorRecursive::PAGES_ONLY, $this->_level);
    }

    public function getMixableMethods($exclude = array())
    {
        $methods = array();
        if($this->getMixer() instanceof KModelEntityInterface) {
            $methods = parent::getMixableMethods($exclude);
        }

        return $methods;
    }

    protected function _afterFetch(KModelContext $context)
    {
        if ($context->state->recurse)
        {
            $pages = $context->entity;

            //Store the pages
            $this->__pages = $pages;

            //Store the level for iteration
            $this->_level    = $context->state->level;

            //Filter children
            foreach ($pages as $path => $page)
            {
                //Mixin the behavior
                $page->mixin($this);

                if($pages->find($page->path))
                {
                    //Get the parent
                    $parent = $page->path;

                    //Store the nodes by parent
                    $this->__children[$parent][$path] = $page;
                }
                else $this->__parents[] = $page;
            }

            //Mixin the behavior
            $pages->mixin($this);

            //Sort the children
            ksort($this->__children);
        }
    }
}