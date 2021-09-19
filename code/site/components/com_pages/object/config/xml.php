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
        $data = ComPagesDomDocument::fromString($string, false);
        return $object ? $this->merge($data) : $data;
    }

    public function toString()
    {
        return ComPagesDomDocument::fromArray($this->toArray(), false)->toString();
    }

    public function fromArray($array, $object = true)
    {
        if($array instanceof KObjectConfigInterface) {
            $array = $array->toArray();
        }

        $document = ComPagesDomDocument::fromArray($array, false);
        return $object ? $this->merge($document) : $document;
    }
}

class ComPagesDomDocument extends \DOMDocument implements \Countable, \IteratorAggregate
{
    private $__xpath;
    private $__selector;

    /**
     * Is this a html document
     *
     * @var bool
     */
    public $html = false;

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
     * Load from a string either as xml or html
     *
     * @param string $string	The string to load
     * @param bool   $html		Load string as html. Default false
     * @return ComPagesDomDocument
     */
    public static function fromString($string, $html = false)
    {
        //Create dom document
        $document = new static();

        if (is_string($string) && trim($string) !== '')
        {
            $errors   = libxml_use_internal_errors(true);
            $entities = libxml_disable_entity_loader(true);

            if(!$html)
            {
                if(substr($string, 0, 5) !== '<?xml') {
                    $string = '<?xml version="1.0" encoding="UTF-8" ?>'.$string;
                }

                $result = $document->loadXml($string, LIBXML_COMPACT);
            }
            else
            {
                if(substr($string, 0, 9) !== '<!DOCTYPE') {
                    $string = '<!DOCTYPE html>'.$string;
                }

                $result = $document->loadHtml($string, LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            }

            //Throw an exception in case an error was found loading the xml/html
            if($result === false)
            {
                $messages  = array();
                foreach (libxml_get_errors() as $error)
                {
                    $message = '';

                    switch ($error->level)
                    {
                        case LIBXML_ERR_WARNING: $message = "Warning $error->code: "; break;
                        case LIBXML_ERR_ERROR  : $message = "Error $error->code: "; break;
                        case LIBXML_ERR_FATAL  : $message = "Fatal Error $error->code: ";break;
                    }

                    $messages[] = sprintf("%s %s on line: %s, column: %s", $message, trim($error->message), $error->line, $error->column);
                }

                //Do not show the same message twice
                throw new \DomainException(implode('<br>', array_unique($messages)));
            }

            libxml_clear_errors();

            libxml_use_internal_errors($errors);
            libxml_disable_entity_loader($entities);

            $document->normalizeDocument();
        }

        $document->html = $html;

        return $document;
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
     * Load from an array
     *
     * @param array				The data to load
     * @param bool   $html		Load string as html. Default false
     * @return ComPagesDomDocument
     */
    public static function fromArray(array $data, $html = false)
    {
        //Create dom document
        $document = new static();

        $fromArray = function(\DOMNode $node, $data) use($document, &$fromArray)
        {
            //Create value and attributes
            if (is_array($data))
            {
                if (array_key_exists('@attributes', $data) && is_array($data['@attributes']))
                {
                    if($node instanceof \DOMElement)
                    {
                        foreach ($data['@attributes'] as $key => $value) {
                            $node->setAttribute($key, $document->encodeValue($value));
                        }
                    }

                    unset($data['@attributes']);
                }

                if (array_key_exists('@fragment', $data))
                {
                    $value = $document->encodeValue($data['@fragment']);

                    $fragment = $document->createDocumentFragment();
                    $fragment->appendXML($value);

                    $node->appendChild($fragment);

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
                                $node->appendChild($fromArray($document->createElement($key), $v));
                            }
                        }
                        else
                        {
                            if($key != '@fragment')
                            {
                                if($key == '@value') {
                                    $node->appendChild($document->createTextNode($value));
                                } else {
                                    $node->appendChild($fromArray($document->createElement($key), $value));
                                }
                            }
                        }

                        unset($data[$key]); //remove the key from the array once done.
                    }
                }
                else
                {
                    //Create siblling nodes using recursion
                    foreach($data as $item) {
                        $node = $fromArray($node, $item);
                    }
                }
            }
            //Append any text values
            else $node->appendChild($document->createTextNode($document->encodeValue($data)));

            return $node;
        };

        //Import the array
        $fromArray($document, $data);

        $document->normalizeDocument();
        $document->html = $html;

        return $document;
    }

    /**
     * Given an name of a node, CSS selector, or XPath expression get a list of nodes.
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @throws InvalidArgumentException If the selector is not valid
     * @return ComPagesDomNodeList  A ComPagesDomNodeList
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
                        $this->__selector = new \Symfony\Component\CssSelector\CssSelectorConverter($this->isHtml());
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
     * @return ComPagesDomNodeList|mixed  A ComPagesDomNodeList or a typed result if possible
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
     * @param string|array|DOMNodeList|ComPagesDomNodeList $nodes
     * @param string $selector Element name, CSS Selector, or Xpath expression
     * @return ComPagesDomDocument
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
            $document = self::fromArray($nodes, $this->isHtml());
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
     * @param string|array|DOMNodeList|ComPagesDomNodeList $selector The element name, css selector or xpath expression
     * 																  of the element(s) to rename
     * @param string $name     The new element name
     * @return ComPagesDomDocument
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
     * @param string|array|DOMNodeList|ComPagesDomNodeList $selector The element name, css selector or xpath expression
     * 																  of the element(s) to rename
     * @return ComPagesDomDocument
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
     * @return ComPagesDomDocument
     */
    public function tranform($xsl)
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
     * @return ComPagesDomDocument
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
     * @return ComPagesDomDocument
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
            if($this->isHtml()) {
                $result[] = parent::saveHTML($node);
            } else {
                $result[] = parent::saveXML($node);
            }
        }

        return implode($this->formatOutput ? "\n": '', $result);
    }

    /**
     * Defined by IteratorAggregate
     *
     * @return ComPagesDomRecursiveIterator
     */
    public function getIterator()
    {
        return new ComPagesDomRecursiveIterator($this->toArray($this), $this);
    }

    /**
     * Create a node list
     *
     * @param  array|Traversable $nodes Array of nodes to use
     * @return ComPagesDomNodeList
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
        return new ComPagesDomNodeList($nodes, $this);
    }

    /**
     * Is this a html document
     *
     * @return boolean
     */
    public function isHtml()
    {
        return $this->html;
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
     * Proxy undefined methods to ComPagesDomNodeList
     *
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments = array())
    {
        if(method_exists('ComPagesDomNodeList', $method)) {
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
        $result = '';

        //Not allowed to throw exceptions in __toString() See : https://bugs.php.net/bug.php?id=53648
        try {
            $result = $this->toString();
        } catch (Exception $e) {
            trigger_error('ComPagesDomDocument::__toString exception: '. (string) $e, E_USER_ERROR);
        }

        return $result;
    }
}

class ComPagesDomNodeList implements \Countable, \IteratorAggregate, \ArrayAccess
{
    private $__document;
    private $__nodes = [];

    public function __construct($list, ComPagesDomDocument $document)
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
        array_reverse($this->__nodes);
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
            if($nodes instanceof Traversable ) {
                $nodes = iterator_to_array($nodes);
            }

            $this->__nodes = array_merge($this->__nodes, $nodes);
        }

        return $this;
    }

    public function getDocument() {
        return $this->__document;
    }

    public function getIterator() {
        return new ComPagesDomRecursiveIterator($this->__nodes);
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
        $document->html = $this->getDocument()->isHtml();
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
        $result = '';

        //Not allowed to throw exceptions in __toString() See : https://bugs.php.net/bug.php?id=53648
        try {
            $result = $this->toString();
        } catch (Exception $e) {
            trigger_error('ComPagesDomDocument::__toString exception: '. (string) $e, E_USER_ERROR);
        }

        return $result;
    }
}

class ComPagesDomRecursiveIterator extends RecursiveArrayIterator
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