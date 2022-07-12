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
        $this->registerNodeClass('DOMDocument', 'ExtDomparserDocument');
        $this->registerNodeClass('DOMElement' , 'ExtDomparserDocumentElement');
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
     * Given a name of a node, CSS selector, or XPath expression get a list of nodes
     * matching the specified group of selectors
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentElementList  A ExtDomparserDocumentElementList
     */
    public function query($selector = null, callable $filter = null)
    {
        $result = array();

        //Get the node list
        if(is_string($selector))
        {
            //If not element name, and not xpath expression (only recognise xpatx expressions if selector starts with "/")
            if(!preg_match('/^\w+$/', $selector) && !str_starts_with($selector, '/'))
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

        //Create the element list
        $nodes = $this->getElements($result);

        //Filter nodes
        if(!is_null($filter)) {
            $nodes = $nodes->map($filter);
        }

        //Create the node list
        return $nodes;
    }

    /**
     * Given an name of a node, CSS selector, or XPath expression count the nodes
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @return int
     */
    public function count($selector = null)
    {
        return $this->query($selector)->count();
    }

    /**
     * Given a name of a node, CSS selector, or XPath expression create a new document
     * from the list of nodes matching the specified group of selectors
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocument  A ExtDomparserDocument
     */
    public function filter($selector = null, callable $filter = null)
    {
        $nodes = $this->query($selector, $filter);

        //Create new document
        $document = new static();
        $document->xml = $document->isXml();
        $document->merge($nodes);

        return $document;

    }

    /**
     * Evaluates the given XPath expression and returns a typed result if possible
     *
     * @param string $expression The XPath expression to execute.
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentElementList|mixed  A ExtDomparserDocumentElementList or a typed result if possible
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
            $result = $this->getElements($result);
        }

        return $result;
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
     * Merge a list of nodes by selector
     *
     * @param string|array|DOMNodeList|ExtDomparserDocumentElementList $nodes
     * @param string $selector Element name, CSS Selector, or Xpath expression
     * @return ExtDomparserDocument
     */
    public function merge($nodes, $selector = null)
    {
        //Handle fragment string
        if(is_string($nodes))
        {
            $fragment = $this->createDocumentFragment();
            $fragment->appendXML($nodes);
            $fragment->normalize();

            $nodes = $this->getElements($fragment->childNodes);

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
            $nodes = $document->query('*');
        }

        if($selector) {
            $targets = $this->query($selector);
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
     * @return ExtDomparserDocumentElementIterator
     */
    public function getIterator()
    {
        return new ExtDomparserDocumentElementIterator($this->toArray($this), $this);
    }

    /**
     * Create a node list
     *
     * @param  array|Traversable $nodes Array of nodes to use
     * @return ExtDomparserDocumentElementList
     */
    public function getElements($nodes = null)
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
        return new ExtDomparserDocumentElementList($nodes, $this);
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
     * Proxy undefined methods to ExtDomparserElementList
     *
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments = array())
    {
        if(method_exists('ExtDomparserDocumentElementList', $method)) {
            throw new \BadMethodCallException("Call to undefined method " . get_class($this) . '::' . $method . "()");
        }

        $nodes = $this->query();

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