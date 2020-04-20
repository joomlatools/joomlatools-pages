<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesObjectConfigXml extends KObjectConfigXml
{
    public function fromString($string, $object = true)
    {
        $data = array();

        if(!empty($string))
        {
            $dom = new DOMDocumentXml('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;

            if($dom->loadXml($string) === false) {
                throw new DomainException('Cannot parse XML string');
            }

            $data = $this->_domToArray($dom);
        }

        return $object ? $this->merge($data) : $data;
    }

    public function toString()
    {
        return $this->toDom()->saveXML();
    }

    public function toDom()
    {
        $data   = $this->toArray();

        $dom = new DOMDocumentXml('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $this->_arrayToDom($dom, $dom, $data);

        return $dom;
    }

    protected function _domToArray(DomNode $node)
    {
        $result = array();

        if ($node->hasAttributes())
        {
            $attributes = $node->attributes;
            foreach ($attributes as $attribute) {
                $result['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        if ($node->hasChildNodes())
        {
            $children = $node->childNodes;
            if ($children->length == 1)
            {
                $child = $children->item(0);
                if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE]))
                {
                    $result['@value'] = $child->nodeValue;
                    return count($result) == 1 ? $result['@value'] : $result;
                }
            }

            $groups = array();
            foreach ($children as $child)
            {
                if($child->nodeType == XML_TEXT_NODE)
                {
                    if(!ctype_space($child->nodeValue)) {
                        $result['@text'] = $child->nodeValue;
                    }

                    continue;
                }

                if($child->nodeType == XML_COMMENT_NODE)
                {
                    if(!ctype_space($child->nodeValue)) {
                        $result['@comment'] = $child->nodeValue;
                    }

                    continue;
                }

                if (isset($result[$child->nodeName]) && $result[$child->nodeName])
                {
                    if (!isset($groups[$child->nodeName]))
                    {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }

                    $result[$child->nodeName][] = $this->_domToArray($child);
                }
                else $result[$child->nodeName] = $this->_domToArray($child);
            }
        }

        return $result;
    }

    protected function _arrayToDom(DOMDocument $xml, $node, $data)
    {
        //Create value and attributes
        if (is_array($data))
        {
            // get the attributes first.;
            if (array_key_exists('@attributes', $data) && is_array($data['@attributes']))
            {
                if(!$node instanceof DOMDocument)
                {
                    foreach ($data['@attributes'] as $key => $value) {
                        $node->setAttribute($key, $this->_encodeValue($value));
                    }
                }

                unset($data['@attributes']);
            }

            if (array_key_exists('@value', $data))
            {
                if(!$node instanceof DOMDocument)
                {
                    $node->appendChild($xml->createTextNode($this->_encodeValue($data['@value'])));
                    unset($data['@value']);
                }

                return $node;
            }
        }


        if (is_array($data))
        {
            //Create child nodes using recursion
            if(!is_numeric(key($data)))
            {
                // recurse to get the node for that key
                foreach ($data as $key => $value)
                {
                    if (is_array($value) && is_numeric(key($value)))
                    {
                        foreach ($value as $k => $v) {
                            $node->appendChild($this->_arrayToDom($xml, $xml->createElement($key), $v));
                        }
                    }
                    else
                    {
                        if($key == '@text') {
                            $node->appendChild($xml->createTextNode($value));
                        } elseif($key == '@comment') {
                            $node->appendChild($xml->createComment($value));
                        } else {
                            $node->appendChild($this->_arrayToDom($xml, $xml->createElement($key), $value));
                        }
                    }

                    unset($data[$key]); //remove the key from the array once done.
                }
            }
            else
            {
                //Create siblling nodes using recursion
                foreach($data as $item) {
                    $node = $this->_arrayToDom($xml, $node, $item);
                }
            }
        }
        //Append any text values
        else  $node->appendChild($xml->createTextNode($this->_encodeValue($data)));

        return $node;
    }


    protected function _encodeValue($value)
    {
        //Convert boolean to text value
        $value = $value === true ? 'true' : $value;
        $value = $value === false ? 'false' : $value;

        return $value;
    }
}

class DomDocumentXml extends DOMDocument
{
    private $__xpath;

    public function __toString() {
        return parent::saveXML();
    }

    public function query($expression)
    {
        if(!isset($this->__xpath)) {
            $this->__xpath =  new DOMXPath($this);
        }

        return $this->__xpath->query($expression);
    }

    public function evaluate($expression)
    {
        if(!isset($this->__xpath)) {
            $this->__xpath =  new DOMXPath($this);
        }

        return $this->__xpath->evaluate($expression);
    }
}
