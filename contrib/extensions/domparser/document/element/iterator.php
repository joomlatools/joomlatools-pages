<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserDocumentElementIterator extends RecursiveArrayIterator
{
    public function __construct($nodes)
    {
        if($nodes instanceof Traversable ) {
            $nodes = iterator_to_array($nodes);
        }

        parent::__construct($nodes);
    }

    public function hasChildren()
    {
        if ($this->valid()) {
            return $this->current()->hasChildNodes();
        }

        return false;
    }

    public function getChildren()
    {
        $nodes = [];

        if ($this->valid()) {
            $nodes = $this->current()->childNodes;
        }

        return new static($nodes);
    }
}