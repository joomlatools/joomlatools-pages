<?php
/**
 * Joomlatools Framework Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelIteratorRecursive extends \RecursiveIteratorIterator
{
    public function __construct($entity, $max_level = 0)
    {
        parent::__construct(static::_createInnerIterator($entity), \RecursiveIteratorIterator::SELF_FIRST);

        //Set the max iteration level
        if(isset($max_level)) {
            $this->setMaxLevel($max_level);
        }
    }

    public function callGetChildren()
    {
        return static::_createInnerIterator($this->current()->getChildren());
    }

    public function callHasChildren()
    {
        $result = false;

        if($this->current()->isRecursable()) {
            $result = $this->current()->hasChildren();
        }

        return $result;
    }

    public function setMaxLevel($max = 0)
    {
        //Set the max depth for the iterator
        $this->setMaxDepth((int) $max - 1);
        return $this;
    }

    public function getLevel()
    {
        return (int) $this->getDepth() + 1;
    }

    protected static function _createInnerIterator($entity)
    {
        $iterator = new \RecursiveArrayIterator($entity);
        $iterator = new \RecursiveCachingIterator($iterator, \CachingIterator::TOSTRING_USE_KEY);

        return $iterator;
    }
}