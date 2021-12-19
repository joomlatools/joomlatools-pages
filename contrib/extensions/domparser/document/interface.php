<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

interface ExtDomparserDocumentInterface extends \Countable, \IteratorAggregate
{
    /**
     * Convert to UTF8
     *
     * @param string $html
     * @return string The converted string
     */
    public function loadHTML($html, $options = 0);

    /**
     * Given an name of a node, CSS selector, or XPath expression get a list of nodes.
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentNodelist  A ExtDomparserDocumentNodelist
     */
    public function filter($selector, $value = null, $exclude = false);

    /**
     * Evaluates the given XPath expression and returns a typed result if possible
     *
     * @param string $expression The XPath expression to execute.
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentNodelist|mixed  A ExtDomparserDocumentNodelist or a typed result if possible
     */
    public function evaluate($expression);

    /**
     * Given a name of a node, CSS selector, or XPath expression count the nodes
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @return int
     */
    public function count($selector = null);

    /**
     * Append a list of nodes
     *
     * @param string|array|DOMNodeList|ExtDomparserDocumentNodelist $nodes
     * @param string $selector Element name, CSS Selector, or Xpath expression
     * @return ExtDomparserDocument
     */
    public function append($nodes, $selector = null);

    /**
     * Rename element(s)
     *
     * @param string $name     The new element name
     * @param string|array|DOMNodeList|ExtDomparserDocumentNodelist $selector The element name, css selector or xpath expression
     * 																  of the element(s) to rename
     * @return ExtDomparserDocument
     */
    public function rename($name, $selector = null);

    /**
     * Remove element(s)
     *
     * @param string|array|DOMNodeList|ExtDomparserDocumentNodelist $selector The element name, css selector or xpath expression
     * 																  of the element(s) to rename
     * @return ExtDomparserDocument
     */
    public function remove($selector = null);

    /**
     * Given an XSL string transform the document
     *
     * @param string $xsl XSL Transormation
     * @return ExtDomparserDocument
     */
    public function transform($xsl);

    /**
     * Given a name of a node, CSS selector, or XPath expression get the text content
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector 			  Element name, CSS Selector, or Xpath expression
     * @param bool  $normalize_whitespace Whether whitespaces should be trimmed and normalized to single space
     * @return string
     */
    public function getText($selector = null, $normalize_whitespace = true);

    /**
     * Get an attribute by name
     *
     * @param string  $name    The attribute name to get
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							get the attribute from
     * @return array|string
     */
    public function getAttribute($name, $selector = null);

    /**
     * Get attributes
     *
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							get the attributes from
     * @return array
     */
    public function getAttributes($selector = null);

    /**
     * Set an attribute
     *
     * @param string $name        The name of the attribute to set
     * @param string $value       The value of the attribue to set
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocument
     */
    public function setAttribute($name, $value, $selector = null);

    /**
     * Set attributes
     *
     * @param iterable $attribues   Array containing the attribute name value pairs to add
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocument
     */
    public function setAttributes(iterable $attributes, $selector = null);

    /**
     * Remove an attribute by name
     *
     * @param string $attribute The attribute name to remove
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							remove attributes from
     * @return ExtDomparserDocument
     */
    public function removeAttribute($name, $selector = null);

    /**
     * Remove attributes
     *
     * @param iterable $attributes  The attribute names to remove
     * @param string $selector The element name, css selector or xpath expression of the element(s) to
     * 							remove attributes from
     * @return ExtDomparserDocument
     */
    public function removeAttributes(iterable $attributes, $selector = null);

    /**
     * Get class(es)
     *
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocumentAttributes
     */
    public function getClass($selector = null);

    /**
     * Add class(es)
     *
     * @param string|array $class Class or array of classes to add
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocument
     */
    public function addClass($class, $selector = null);

    /**
     * Add class(es)
     *
     * @param string|array $class Class or array of classes to remove
     * @param string $selector    The element name, css selector or xpath expression of the element(s)
     * 							  to add attributes too
     * @return ExtDomparserDocument
     */
    public function removeClass($class, $selector = null);

    /**
     * Return an associative array of dom data
     *
     * @return array
     */
    public function toArray(\DOMNode $node = null);

    /**
     * Cast to a string
     *
     * @param bool|null $pretty_print Nicely formats output with indentation and extra space. If NULL use object setting
     * @return string
     */
    public function toString($pretty_print = null);

    /**
     * Defined by IteratorAggregate
     *
     * @return ExtDomparserDocumentIterator
     */
    public function getIterator();

    /**
     * Create a node list
     *
     * @param  array|Traversable $nodes Array of nodes to use
     * @return ExtDomparserDocumentNodelist
     */
    public function getNodeList($nodes = null);

    /**
     * Is this a xml document
     *
     * @return boolean
     */
    public function isXml();

    /**
     * Encode value to text variant
     *
     * @param $value
     * @return string
     */
    public function encodeValue($value);
}