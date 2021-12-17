<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserDocumentNodelist implements \Countable, \IteratorAggregate, \ArrayAccess
{
    private $__document;
    private $__nodes = [];

    public function __construct($list, ExtDomparserDocument $document)
    {
        foreach ($list as $node) {
            $this->__nodes[] = $node;
        }

        $this->__document  = $document;
    }

    public function first()
    {
        $result = null;
        if(!empty($this->__nodes))
        {
            if($node = reset($this->__nodes)) {
                $result = new static($node, $this->__document);
            }
        }

        return $result;
    }

    public function item($key)
    {
        $result = null;
        if(isset($this->__nodes[$key])) {
            $result = new static($this->__nodes[$key], $this->__document);
        }

        return $result;
    }

    public function last()
    {
        $result = null;
        if(!empty($this->__nodes))
        {
            if($node = end($this->__nodes)) {
                $result = new static($node, $this->__document);
            }
        }

        return $result;
    }

    public function name()
    {
        $result = null;

        if($node = current($this->__nodes)) {
            $result = $node->nodeName;
        }

        return $result;
    }

    public function text($default = null)
    {
        $result = $default;

        if($node = current($this->__nodes)) {
            $result = $node->textContent;
        }

        return $result;
    }

    public function attribute($name, $default = null)
    {
        $result = $default;
        if($node = current($this->__nodes))
        {
            if($node->hasAttribute($name)) {
                $result = $node->getAttribute($name);
            }
        }

        return $result;
    }


    public function shuffle()
    {
        shuffle($this->__nodes);
        return $this;
    }

    public function slice($offset, $length = NULL)
    {
        $nodes = array_slice($this->__nodes, $offset, $length);
        return new static($nodes, $this->getDocument());
    }

    public function reverse()
    {
        $this->__nodes = array_reverse($this->__nodes);
        return $this;
    }

    public function count() {
        return count($this->__nodes);
    }

    public function contains(DOMNode $node) {
        return in_array($node, $this->__nodes, true);
    }

    public function reduce(callable $function, $initial = null)
    {
        if(is_null($initial)) {
            $initial = new static([], $this->__document);
        }

        return array_reduce($this->__nodes, $function, $initial);
    }

    public function merge($elements)
    {
        if(is_array($elements) || $elements instanceof \Traversable)
        {
            if($elements instanceof Traversable ) {
                $elements = iterator_to_array($elements);
            }

            $this->__nodes = array_merge($this->__nodes, $elements);
        }

        return $this;
    }

    public function getDocument() {
        return $this->__document;
    }

    public function getIterator() {
        return new ExtDomparserDocumentIterator($this->__nodes);
    }

    public function offsetExists($offset) {
        return isset($this->__nodes[$offset]);
    }

    public function offsetGet($offset){
        return isset($this->__nodes[$offset]) ? $this->__nodes[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->__nodes[] = $value;
        } else {
            $this->__nodes[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->__nodes[$offset]);
    }

    public function toString(){
        return $result = $this->__call('toString');
    }

    public function __debugInfo(){
        return $this->__nodes;
    }

    public function __call($method, $arguments = array())
    {
        if(!method_exists($this->getDocument(), $method)) {
            throw new \BadMethodCallException("Call to undefined method " . get_class($this) . '::' . $method . "()");
        }

        //Create new document
        $class = get_class($this->__document);
        $document = new $class();
        $document->xml = $this->getDocument()->isXml();
        $document->append($this);

        return call_user_func_array([$document, $method], $arguments);
    }

    public function __clone()
    {
        $array = array();
        foreach($this->__nodes as $key => $value)
        {
            if ($value instanceof DOMNode) {
                $array[$key] = $value->cloneNode(true);
            } else {
                $array[$key] = $value;
            }
        }

        $this->__nodes = $array;
    }

    final public function __toString()
    {
        return $result = $this->toString();;
    }
}