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
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;

            if($dom->loadXml($string) === false) {
                throw new DomainException('Cannot parse XML string');
            }

            $data = $this->_domToArray($dom->documentElement);
        }

        return $object ? $this->merge($data) : $data;
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
                if (isset($result[$child->nodeName]))
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
}