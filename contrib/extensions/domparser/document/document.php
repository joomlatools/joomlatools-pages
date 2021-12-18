<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserDocument extends \DOMDocument implements ExtDomparserDocumentInterface
{
    private $__xpath;
    private $__selector;

    /**
     * Is this an xml document
     *
     * @var bool
     */
    public $xml = false;

    /**
     * Constructor.
     *
     * @param string $version  The version number of the document as part of the XML declaration.
     * @param string $encoding The encoding of the document as part of the XML declaration.
     */
    public function __construct($version = '1.0', $encoding ='UTF-8')
    {
        parent::__construct($version, $encoding);

        $this->formatOutput       = true;
        $this->preserveWhiteSpace = true;
    }

    /**
     * Convert to UTF8
     *
     * @param string $html
     * @return string The converted string
     */
    public function loadHTML($html, $options = 0)
    {
        $encoding = null;
        if (preg_match('@<meta.*?charset=["\']?([^"\'\s>]+)@im', $html, $matches)) {
            $encoding = mb_strtoupper($matches[1]);
        }

        //Only try to detect UTF-8
        if(!$encoding) {
            $encoding = mb_detect_encoding($html, ['UTF-8'], true);
        }

        //Convert if the encoding is not UTF-8
        if($encoding != 'UTF-8')
        {
            $result = false;

            // Fallback to iconv if available.
            if (!in_array($encoding, array_map('mb_strtoupper', mb_list_encodings())))
            {
                if (extension_loaded('iconv')) {
                    $result = iconv($encoding, 'UTF-8', $html);
                }
            }
            else $result = mb_convert_encoding($html, 'UTF-8', $encoding);

            //Return the converted html
            if($result) {
                $html = preg_replace('@(charset=["]?)([^"\s]+)([^"]*["]?)@im', '$1utf-8$3', $result);
            }
        }

        return parent::loadHTML($html, $options);
    }

    /**
     * Given a name of a node, CSS selector, or XPath expression get a list of nodes.
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentNodelist  A ExtDomparserDocumentNodelist
     */
    public function filter($selector, $value = null, $exclude = false)
    {
        $result = array();

        //Get the node list
        if(is_string($selector))
        {
            if(strpos($selector, '/') === false)
            {
                if (class_exists('\Symfony\Component\CssSelector\CssSelectorConverter'))
                {
                    if (!isset($this->__selector)) {
                        $this->__selector = new \Symfony\Component\CssSelector\CssSelectorConverter(!$this->isXml());
                    }

                    $selector = $this->__selector->toXPath($selector);
                }
            }

            if(!isset($this->__xpath)) {
                $this->__xpath = new \DOMXPath($this);
            }

            //Evaluate the expression
            if(false === $result = $this->__xpath->query($selector, $this)) {
                throw new InvalidArgumentException(sprintf('Selector: %s is not valid', $selector));
            }
        }
        else $result = $selector;

        //Create the node list
        $nodes = $this->getNodeList($result);

        //Filter value
        if(!is_null($value))
        {
            $nodes = $nodes->reduce(function($list, $node) use($value, $exclude)
            {
                if($exclude)
                {
                    if($node->nodeValue !== $value) {
                        $list[] = $node;
                    }
                }
                else
                {
                    if($node->nodeValue === $value) {
                        $list[] = $node;
                    }
                }

                return $list;
            });
        }

        //Create the node list
        return $nodes;
    }

    /**
     * Evaluates the given XPath expression and returns a typed result if possible
     *
     * @param string $expression The XPath expression to execute.
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentNodelist|mixed  A ExtDomparserDocumentNodelist or a typed result if possible
     */
    public function evaluate($expression)
    {
        if(!isset($this->__xpath)) {
            $this->__xpath = new \DOMXPath($this);
        }

        //Evaluate the expressiona
        if(false === $result = $this->__xpath->evaluate($expression, $this)) {
            throw new InvalidArgumentException(sprintf('Expression: %s is not valid', $expression));
        }

        if($result instanceof \DOMNodeList) {
            $result = $this->getNodeList($result);
        }

        return $result;
    }

    /**
     * Given an name of a node, CSS selector, or XPath expression get the text content
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector 			  Element name, CSS Selector, or Xpath expression
     * @param bool  $normalize_whitespace Whether whitespaces should be trimmed and normalized to single space
     * @return string
     */
    public function text($selector = '*', $normalize_whitespace = true)
    {
        $result = $this->filter($selector)->reduce(function($carry, $node) {
            return $carry .= $node->textContent;
        }, '');

        if ($normalize_whitespace) {
            $result = trim(preg_replace('/(?:\s{2,}+|[^\S ])/', ' ', $result));
        }

        return $result;
    }

    /**
     * Given an name of a node, CSS selector, or XPath expression count the nodes
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @return int
     */
    public function count($selector = '*')
    {
        return $this->filter($selector)->count();
    }

    /**
     * Append a list of nodes
     *
     * @param string|array|DOMNodeList|ExtDomparserDocumentNodelist $nodes
     * @param string $selector Element name, CSS Selector, or Xpath expression
     * @return ExtDomparserDocument
     */
    public function append($nodes, $selector = null)
    {
        //Handle fragment string
        if(is_string($nodes))
        {
            $fragment = $this->createDocumentFragment();
            $fragment->appendXML($nodes);
            $fragment->normalize();

            $nodes = $this->getNodeList($fragment->childNodes);

            // Remove empty text nodes
            foreach($nodes as $key => $node)
            {
                if($node instanceof \DOMCharacterData && !$node->length) {
                    unset($nodes[$key]);
                }
            };
        }

        //Handle data array
        if(is_array($nodes))
        {
            $document = ExtDomparserDocumentFactory::fromArray($nodes, !$this->isXml());
            $nodes = $document->filter('*');
        }

        if($selector) {
            $targets = $this->filter($selector);
        }

        //Append the nodes to the document
        foreach($nodes as $node)
        {
            $node = $this->importNode($node, true);

            if($selector)
            {
                foreach($targets as $target) {
                    $target->appendChild($node);
                }
            }
            else $this->appendChild($node);
        }

        return $this;
    }

    /**
     * Rename element(s)
     *
     * @param string|array|DOMNodeList|ExtDomparserDocumentNodelist $selector The element name, css selector or xpath expression
     * 																  of the element(s) to rename
     * @param string $name     The new element name
     * @return ExtDomparserDocument
     */
    public function rename($selector, $name)
    {
        if($nodes = $this->filter($selector))
        {
            foreach($nodes as $oldNode)
            {
                $newNode = $this->createElement($name);

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
        }

        return $this;
    }

    /**
     * Remove element(s)
     *
     * @param string|array|DOMNodeList|ExtDomparserDocumentNodelist $selector The element name, css selector or xpath expression
     * 																  of the element(s) to rename
     * @return ExtDomparserDocument
     */
    public function remove($selector)
    {
        if($nodes = $this->filter($selector))
        {
            foreach($nodes as $key => $node)
            {
                $node->parentNode->removeChild($node);
                unset($nodes[$key]);
            }
        }

        return $this;
    }

    /**
     * Given an XSL string transform the document
     *
     * @param string $xsl XSL Transormation
     * @return ExtDomparserDocument
     */
    public function transform($xsl)
    {
        $xsl  = new \DomDocument($xsl);
        $xslt = new \XSLTProcessor();
        $xslt->importStylesheet($xsl);

        return new static($xslt->transformToDoc($this));
    }

    /**
     * Get attributes
     *
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							get the attributes from
     * @param array  $names    The attribute names to remove
     * @return array
     */
    public function getAttributes($selector)
    {
        $attributes = array();
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement)
            {
                if($node->hasAttributes())
                {
                    foreach($node->attributes as $key => $attribute)
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
            }
        }

        return $attributes;
    }

    /**
     * Remove attributes
     *
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							remove attributes from
     * @param array  $names    The attribute names to remove
     * @return ExtDomparserDocument
     */
    public function removeAttributes($selector, $attributes)
    {
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement)
            {
                foreach((array) $attributes as $name) {
                    $node->removeAttribute($name);
                }
            }
        }

        return $this;
    }

    /**
     * Add attributes
     *
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @param array  $attribues   Array containing the attribute name value pairs to add
     * @return ExtDomparserDocument
     */
    public function addAttributes($selector, array $attributes, $replace = false)
    {
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement)
            {
                foreach($attributes as $name => $value)
                {
                    if(!$replace && $node->hasAttribute($name))
                    {
                        $value = array_merge((array) $value, explode(' ', $node->getAttribute($name)));
                        $node->setAttribute($name, implode(' ', array_unique($value)));
                    }
                    else $node->setAttribute($name, implode(' ', $value));
                }
            }
        }

        return $this;
    }

    /**
     * Return an associative array of dom data
     *
     * @return array
     */
    public function toArray(\DOMNode $node = null)
    {
        $result = array();

        //If no node provided use self
        if(!$node) {
            $node = $this;
        }

        if ($node->hasAttributes())
        {
            $attributes = $node->attributes;
            foreach ($attributes as $attribute) {
                $result['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        if ($node->hasChildNodes())
        {
            $children   = $node->childNodes;
            $firstChild = $node->firstChild;

            //Node contains a single text child node
            if ($children->length == 1 && $firstChild instanceof \DOMCharacterData )
            {
                if($node->firstChild instanceof \DOMText) {
                    $result['@value'] = $firstChild->nodeValue;
                }

                if ($node->firstChild instanceof \DOMCdataSection) {
                    $result['@value'] = $firstChild->wholeText;
                }

                //Do not process comments (WIP)
                /*if ($node->firstChild instanceof \DOMComment) {
                    $result['@value'] = $firstChild->nodeValue;
                }*/

                $result = count($result) == 1 ? $result['@value'] : $result;
            }
            else
            {
                $groups   = array();
                foreach ($children as $child)
                {
                    if($child instanceof \DOMText)
                    {
                        if(isset($result['@value']))
                        {
                            if($node->ownerDocument->preserveWhiteSpace) {
                                $content = trim($node->textContent);
                            } else {
                                $content = $node->textContent;
                            }

                            $result['@value'] = $content;

                            $result['@fragment'] = '';
                            foreach ($node->childNodes as $c) {
                                $result['@fragment'] .= $node->ownerDocument->saveHTML($c);
                            }
                        }
                        else
                        {
                            if(!$node->ownerDocument->preserveWhiteSpace) {
                                $content = $child->nodeValue;
                            } else {
                                $content = trim($child->nodeValue);
                            }

                            if(!empty($content)) {
                                $result['@value'] =  $content;
                            }
                        }

                    }

                    if ($child instanceof \DOMElement)
                    {
                        if (isset($result[$child->nodeName]) && $result[$child->nodeName])
                        {
                            if (!isset($groups[$child->nodeName]))
                            {
                                $result[$child->nodeName] = array($result[$child->nodeName]);
                                $groups[$child->nodeName] = 1;
                            }

                            $result[$child->nodeName][] = $this->toArray($child);
                        }
                        else $result[$child->nodeName] = $this->toArray($child);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Cast to a string
     *
     * @param bool|null $pretty_print Nicely formats output with indentation and extra space. If NULL use object setting
     * @return string
     */
    public function toString($pretty_print = null)
    {
        if(!is_null($pretty_print)) {
            $this->formatOutput = $pretty_print;
        }

        $result = array();
        foreach($this->childNodes as $node)
        {
            if($this->isXml()) {
                $result[] = parent::saveXML($node);
            } else {
                $result[] = parent::saveHTML($node);
            }
        }

        return implode($this->formatOutput ? "\n": '', $result);
    }

    /**
     * Defined by IteratorAggregate
     *
     * @return ExtDomparserDocumentIterator
     */
    public function getIterator()
    {
        return new ExtDomparserDocumentIterator($this->toArray($this), $this);
    }

    /**
     * Create a node list
     *
     * @param  array|Traversable $nodes Array of nodes to use
     * @return ExtDomparserDocumentNodelist
     */
    public function getNodeList($nodes = null)
    {
        if(!is_array($nodes) && !$nodes instanceof Traversable)
        {
            if(is_null($nodes)) {
                $nodes = $this->childNodes;
            } else {
                $nodes = array($nodes);
            }
        }

        //Create a new node list
        return new ExtDomparserDocumentNodelist($nodes, $this);
    }

    /**
     * Is this a xml document
     *
     * @return boolean
     */
    public function isXml()
    {
        return $this->xml;
    }

    /**
     * Encode value to text variant
     *
     * @param $value
     * @return string
     */
    public function encodeValue($value)
    {
        //Convert boolean to text value
        $value = $value === true ? 'true' : $value;
        $value = $value === false ? 'false' : $value;

        return $value;
    }

    /**
     * Proxy undefined methods to ExtDomparserNodelist
     *
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments = array())
    {
        if(method_exists('ExtDomparserDocumentNodelist', $method)) {
            throw new \BadMethodCallException("Call to undefined method " . get_class($this) . '::' . $method . "()");
        }

        //Create new document
        $nodes = $this->filter('*');
        return call_user_func_array([$nodes, $method], $arguments);
    }

    /**
     * Allow PHP casting of this object
     *
     * @return string
     */
    final public function __toString()
    {
        return $this->toString();
    }
}