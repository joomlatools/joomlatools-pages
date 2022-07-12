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
     * Given a name of a node, CSS selector, or XPath expression get a list of nodes
     * matching the specified group of selectors
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentElementList  A ExtDomparserDocumentElementList
     */
    public function query($selector = null, callable $filter = null);

    /**
     * Given a name of a node, CSS selector, or XPath expression create a new document
     * from the list of nodes matching the specified group of selectors
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param string $selector Tag name, CSS Selector, or Xpath expression
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentElementList  A ExtDomparserDocumentElementList
     */
    public function filter($selector, callable $filter = null);

    /**
     * Evaluates the given XPath expression and returns a typed result if possible
     *
     * @param string $expression The XPath expression to execute.
     * @throws InvalidArgumentException If the selector is not valid
     * @return ExtDomparserDocumentElementList|mixed  A ExtDomparserDocumentElementList or a typed result if possible
     */
    public function evaluate($expression);

    /**
     * Given an XSL string transform the document
     *
     * @param string $xsl XSL Transormation
     * @return ExtDomparserDocument
     */
    public function transform($xsl);

    /**
     * Append a list of nodes
     *
     * @param string|array|DOMNodeList|ExtDomparserDocumentElementList $nodes
     * @param string $selector Element name, CSS Selector, or Xpath expression
     * @return ExtDomparserDocument
     */
    public function append($nodes, $selector = null);

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
     * @return ExtDomparserDocumentElementIterator
     */
    public function getIterator();

    /**
     * Create a node list
     *
     * @param  array|Traversable $nodes Array of nodes to use
     * @return ExtDomparserDocumentElementList
     */
    public function getElements($nodes = null);

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