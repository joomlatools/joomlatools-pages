<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserDocumentElement extends \DOMElement implements ExtDomparserDocumentElementInterface
{
    public function getDocument()
    {
        return $this->ownerDocument;
    }

    public function getParent()
    {
        return $this->parentNode;
    }

    public function getName()
    {
        return $this->nodeName;
    }

    public function getType()
    {
        return $this->nodeType;
    }

    public function getValue()
    {
        return $this->nodeValue;
    }

    public function getText($normalize_whitespace = true)
    {
        $result = $this->textContent;

        if ($normalize_whitespace) {
            $result = trim(preg_replace('/(?:\s{2,}+|[^\S ])/', ' ', $result));
        }

        return $result;
    }

    public function setText($text)
    {
        $this->textContent = (string) $text;
        return $this;
    }

    public function getAttributes()
    {
        $attributes = array();
        if ($this->hasAttributes())
        {
            foreach($this->attributes as $attribute)
            {
                $name  = $attribute->name;
                $value = $attribute->value;

                if(isset($attributes[$name]))
                {
                    $value = array_merge((array) $attributes[$name], (array) $value);

                    if(count($value) > 1) {
                        $attributes[$name] = $value;
                    }
                }
                else $attributes[$name] = $value;
            }
        }

        return !empty($attributes) ? new ExtDomparserDocumentAttributes($attributes) : array();
    }

    public function setAttribute($name, $value)
    {
        if(is_array($value)) {
            $value = implode(' ', $value);
        }

        parent::setAttribute($name, $value);
        return $this;
    }

    public function setAttributes(iterable $attributes)
    {
        foreach($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }

        return $this;
    }

    public function removeAttributes(iterable $attributes)
    {
        foreach($attributes as $name) {
            $this->removeAttribute($name);
        }

        return $this;
    }

    public function getClass()
    {
        $classes = array();
        if($this->hasAttribute('class')) {
            $classes = array_merge($classes, preg_split('/\s+/', $this->getAttribute('class')));
        }

        return new ExtDomparserDocumentAttributes(array_values(array_unique($classes)));
    }

    public function hasClass($name)
    {
        $class = (array) $this->getClass();
        return in_array($name, $class);
    }

    public function addClass($class, $selector = null)
    {
        if($this->hasAttribute('class'))
        {
            $value = array_merge((array) $class, preg_split('/\s+/', $this->getAttribute('class')));
            $this->setAttribute('class', implode(' ', array_unique($value)));
        }
        else $this->setAttribute('class', implode(' ', (array) $class));

        return $this;
    }

    public function removeClass($class, $selector = null)
    {
        if($this->hasAttribute('class'))
        {
            $value = array_diff(preg_split('/\s+/', $this->getAttribute('class')), (array) $class);

            if(!empty($value)) {
                $this->setAttribute('class', implode(' ', array_unique($value)));
            } else {
                $this->removeAttribute('class');
            }
        }

        return $this;
    }

    public function hasChildren()
    {
        if ($this->hasChildNodes())
        {
            foreach ($this->childNodes as $node)
            {
                if ($node->nodeType == XML_ELEMENT_NODE) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getChildren()
    {
        $children = array();
        if ($this->hasChildNodes())
        {
            foreach ($this->childNodes as $node)
            {
                if ($node->nodeType == XML_ELEMENT_NODE) {
                    $children[] = $node;
                }
            }
        }

        return new ExtDomparserDocumentElementList($children, $this->getDocument());
    }

    public function toString()
    {
        $document = $this->getDocument();

        //Create new document
        $class = get_class($document);
        $newDocument = new $class();
        $newDocument->xml = $document->isXml();
        $newDocument->merge(new ExtDomparserDocumentElementList([$this], $newDocument));

        return $newDocument->toString();
    }

    final public function __toString()
    {
        return $this->toString();
    }
}