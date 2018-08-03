<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelIteratorRecursive extends \RecursiveIteratorIterator
{
    const PAGES_ONLY   = \RecursiveIteratorIterator::LEAVES_ONLY;
    const PAGES_TREE   = \RecursiveIteratorIterator::CHILD_FIRST;
    const PAGES_STRUCTURE = 3;

    protected $_mode;

    public function __construct($entity, $mode = self::PAGES_TREE, $max_level = 0)
    {
        //Store the mode
        $this->_mode = $mode;

        //Revert the mode
        $mode = ($mode == self::PAGES_STRUCTURE) ? self::PAGES_ONLY : $mode;

        parent::__construct(static::_createInnerIterator($entity), $mode);

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
        if($this->getMode() == self::PAGES_STRUCTURE) {
            $result = !((bool) $this->current()->isCollection() || $this->current()->hasChildren());
        } else {
            $result = (bool) $this->current()->isCollection() || $this->current()->hasChildren();
        }

        return $result;
    }

    public function getMode()
    {
        return $this->_mode;
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