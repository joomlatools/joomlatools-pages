<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserDocumentElementList implements \Countable, \IteratorAggregate, \ArrayAccess
{
    private $__document;
    private $__nodes = [];

    public function __construct(iterable $list, ExtDomparserDocument $document)
    {
        $this->fromArray($list);
        $this->__document = $document;
    }

    public function first()
    {
        $result = null;
        if(!empty($this->__nodes))
        {
            if($node = reset($this->__nodes)) {
                $result = new static([$node], $this->__document);
            }
        }

        return $result;
    }

    public function item($key)
    {
        $result = null;
        $key    = $key - 1;
        if(isset($this->__nodes[$key])) {
            $result = new static([$this->__nodes[$key]], $this->__document);
        }

        return $result;
    }

    public function last()
    {
        $result = null;
        if(!empty($this->__nodes))
        {
            if($node = end($this->__nodes)) {
                $result = new static([$node], $this->__document);
            }
        }

        return $result;
    }

    public function slice($offset, $length = NULL)
    {
        $nodes = array_slice($this->__nodes, $offset, $length);
        return new static($nodes, $this->__document);
    }

    public function count()
    {
        return count($this->__nodes);
    }

    public function contains(\DOMNode $node)
    {
        return in_array($node, $this->__nodes, true);
    }

    public function search(\DOMNode $node)
    {
        return array_search($node, $this->__nodes, true);
    }

    public function each(callable $function)
    {
        foreach ($this->__nodes as $i => $node)
        {
            $result = $function($node, $i);

            if ($result === false) {
                break;
            }
        }

        return $this;
    }

    public function map(callable $function)
    {
        $nodes = array();

        foreach ($this->__nodes as $node)
        {
            $result = $function($node);

            if (!is_null($result) && $result !== false) {
                $nodes[] = $result;
            }
        }

        return new static($nodes, $this->__document);
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

    public function rename($name)
    {
        foreach($this->__nodes as $oldNode)
        {
            $newNode = $this->getDocument()->createElement($name);

            if ($oldNode->attributes->length)
            {
                foreach ($oldNode->attributes as $attribute) {
                    $newNode->setAttribute($attribute->nodeName, $attribute->nodeValue);
                }
            }

            while ($oldNode->firstChild) {
                $newNode->appendChild($oldNode->firstChild);
            }

            $oldNode->parentNode->replaceChild($newNode, $oldNode);
        }

        return $this;
    }

    public function remove()
    {
        foreach($this->__nodes as $key => $node)
        {
            $node->parentNode->removeChild($node);
            unset($this->__nodes[$key]);
        }

        return $this;
    }

    public function getText($normalize_whitespace = true)
    {
        $result = $this->reduce(function($carry, $node) use($normalize_whitespace) {
            return $carry .= $node->getText($normalize_whitespace);
        }, '');

        return $result;
    }

    public function getClass()
    {
        $result = $this->__call(__FUNCTION__);

        if(is_array($result))
        {
            $classes = array();

            foreach($result as $class) {
                $classes = array_merge($classes, (array) KObjectConfig::unbox($class));
            }

            $result = new ExtDomparserDocumentAttributes(array_values(array_unique($classes)));
        }

        return $result;
    }

    public function hasClass($name)
    {
        $class = (array) $this->getClass();
        return in_array($name, $class);
    }

    public function getAttribute($name)
    {
        $result = $this->__call(__FUNCTION__, [$name]);

        if(is_array($result)) {
            $result = new ExtDomparserDocumentAttributes($result);
        }

        return $result;
    }

    public function getAttributes()
    {
        $result = $this->__call(__FUNCTION__);

        if(is_array($result)) {
            $result = new ExtDomparserDocumentAttributes($result);
        }

        return $result;
    }

    public function getDocument()
    {
        return $this->__document;
    }

    public function getIterator()
    {
        return new ExtDomparserDocumentElementIterator($this->__nodes);
    }

    public function offsetExists($offset)
    {
        return isset($this->__nodes[$offset]);
    }

    public function offsetGet($offset)
    {
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

    public function offsetUnset($offset)
    {
        unset($this->__nodes[$offset]);
    }

    public function toArray()
    {
        return $this->__nodes;
    }

    public function fromArray(iterable $nodes = null)
    {
        $this->__nodes = [];

        if (is_iterable($nodes))
        {
            foreach ($nodes as $node) {
                $this->__nodes[] = $node;
            }
        }
    }

    public function toString()
    {
        $document = $this->getDocument();
        return $document->toString();
    }

    public function isRemoved()
    {
        return false;
    }

    public function __debugInfo()
    {
        return $this->toArray();
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

    public function __call($method, $arguments = array())
    {
        $result = array();

        $document = $this->getDocument();
        if(!method_exists($document, $method))
        {
            foreach($this->__nodes as $node)
            {
                if ($node instanceof \DOMElement)
                {
                    $return = call_user_func_array([$node, $method], $arguments);

                    if($return != $node) {
                        $result[] = $return;
                    }
                }
            }

            if(!empty($result)) {
                $result = count($this->__nodes) == 1 && isset($result[0]) ? $result[0] : $result;
            } else {
                $result = $this;
            }
        }
        else $result = call_user_func_array([$document, $method], $arguments);

        return $result;
    }

    final public function __toString()
    {
        return $this->toString();
    }
}