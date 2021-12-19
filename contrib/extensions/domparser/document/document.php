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

        //Dpcument
        $this->registerNodeClass('DOMDocument'    , 'ExtDomparserDocument');

        //Nodes
        $this->registerNodeClass('DOMText'        , 'ExtDomparserDocumentNodeText');
        $this->registerNodeClass('DOMElement'     , 'ExtDomparserDocumentNodeElement');
        $this->registerNodeClass('DOMComment'     , 'ExtDomparserDocumentNodeComment');
        $this->registerNodeClass('DOMDocumentType', 'ExtDomparserDocumentNodeType');
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
    public function filter($selector = null, $value = null, $exclude = false)
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
     * Given an name of a node, CSS selector, or XPath expression count the nodes
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @return int
     */
    public function count($selector = null)
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
     * @param string $name  The new element name
     * @param string|array|DOMNodeList|ExtDomparserDocumentNodelist $selector The element name, css selector or xpath expression
     * 																  of the element(s) to rename
     * @return ExtDomparserDocument
     */
    public function rename($name, $selector = null)
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
    public function remove($selector = null)
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
     * Given an name of a node, CSS selector, or XPath expression get the text content
     *
     * @param string $selector 			  Element name, CSS Selector, or Xpath expression
     * @param bool  $normalize_whitespace Whether whitespaces should be trimmed and normalized to single space
     * @return string
     */
    public function getText($selector = null, $normalize_whitespace = true)
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
     * Get an attribute by name
     *
     * @param string  $name    The attribute name to get
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							get the attribute from
     * @return ExtDomparserDocumentAttributes|string
     */
    public function getAttribute($name, $selector = null)
    {
        $values = array();
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement && $node->hasAttribute($name)) {
                $values[] = $node->getAttribute($name);
            }
        }

        return count($nodes) == 1 ? $values[0] : new ExtDomparserDocumentAttributes($values);
    }

    /**
     * Get attributes
     *
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							get the attributes from
     * @param array  $names    The attribute names to remove
     * @return ExtDomparserDocumentAttributes
     */
    public function getAttributes($selector = null)
    {
        $attributes = array();
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement && $node->hasAttributes())
            {
                foreach($node->attributes as $attribute)
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

        return new ExtDomparserDocumentAttributes($attributes);
    }

    /**
     * Set an attribute
     *
     * @param string $name        The name of the attribute to set
     * @param string $value       The value of the attribue to set
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocument
     */
    public function setAttribute($name, $value, $selector = null)
    {
        return $this->setAttributes([$name => $value], $selector);
    }

    /**
     * Set attributes
     *
     * @param iterable $attribues   Array containing the attribute name value pairs to add
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocument
     */
    public function setAttributes(iterable $attributes, $selector = null)
    {
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement)
            {
                foreach($attributes as $name => $value) 
                {
                    if(is_array($value)) {
                        $value = implode(' ', $value);
                    }
                    
                    $node->setAttribute($name, $value);
                }
            }
        }

        return $this;
    }

    /**
     * Remove an attribute by name
     *
     * @param string $attribute The attribute name to remove
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							remove attributes from
     * @return ExtDomparserDocument
     */
    public function removeAttribute($name, $selector = null)
    {
        return $this->removeAttributes([$name], $selector);
    }

    /**
     * Remove attributes
     *
     * @param iterable $attributes The attribute names to remove
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							remove attributes from
     * @return ExtDomparserDocument
     */
    public function removeAttributes(iterable $attributes, $selector = null)
    {
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement)
            {
                foreach($attributes as $name) {
                    $node->removeAttribute($name);
                }
            }
        }

        return $this;
    }

    /**
     * Get class(es)
     *
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocumentAttributes
     */
    public function getClass($selector = null)
    {
        $classes = array();
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement)
            {
                if($node->hasAttribute('class')) {
                    $classes = array_merge($classes, preg_split('/\s+/', $node->getAttribute('class')));
                }
            }
        }

        return new ExtDomparserDocumentAttributes(array_values(array_unique($classes)));
    }

    /**
     * Add class(es)
     *
     * @param string|array $class Class or array of classes to add
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocument
     */
    public function addClass($class, $selector = null)
    {
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement)
            {
                if($node->hasAttribute('class'))
                {
                    $value = array_merge((array) $class, preg_split('/\s+/', $node->getAttribute('class')));
                    $node->setAttribute('class', implode(' ', array_unique($value)));
                }
                else $node->setAttribute('class', implode(' ', (array) $class));
            }
        }

        return $this;
    }

    /**
     * Add class(es)
     *
     * @param string|array $class Class or array of classes to remove
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocument
     */
    public function removeClass($class, $selector = null)
    {
        $nodes = $this->filter($selector);

        foreach($nodes as $node)
        {
            if ($node instanceof \DOMElement)
            {
                if($node->hasAttribute('class'))
                {
                    $value = array_diff(preg_split('/\s+/', $node->getAttribute('class')), (array) $class);
                    
                    if(!empty($value)) {
                        $node->setAttribute('class', implode(' ', array_unique($value)));
                    } else {
                        $node->removeAttribute('class');
                    }
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
        $nodes = $this->filter();
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