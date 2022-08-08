<?php

/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

interface ExtDomparserDocumentElementInterface
{
    /**
     * Given a name of a node, CSS selector, or XPath expression get the text content
     *
     * @see: https://symfony.com/doc/current/components/css_selector.html
     *
     * @param bool  $normalize_whitespace Whether whitespaces should be trimmed and normalized to single space
     * @return string
     */
    public function getText($normalize_whitespace = true);

    /**
     * Get an attribute by name
     *
     * @param string  $name    The attribute name to get
     * @return array|string
     */
    #public function getAttribute($name);

    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Set an attribute
     *
     * @param string $name        The name of the attribute to set
     * @param string $value       The value of the attribue to set
     * @return self
     */
    public function setAttribute($name, $value);

    /**
     * Set attributes
     *
     * @param iterable $attribues   Array containing the attribute name value pairs to add
     * @return self
     */
    public function setAttributes(iterable $attributes);

    /**
     * Remove an attribute by name
     *
     * @param string $attribute The attribute name to remove
     * @return self
     */
    #public function removeAttribute($name);

    /**
     * Remove attributes
     *
     * @param iterable $attributes  The attribute names to remove
     * @return self
     */
    public function removeAttributes(iterable $attributes);

    /**
     * Get class(es)
     *
     * @return ExtDomparserDocumentAttributes
     */
    public function getClass();

    /**
     * Add class(es)
     *
     * @param string|array $class Class or array of classes to add
     * @return ExtDomparserDocument
     */
    public function addClass($class);

    /**
     * Add class(es)
     *
     * @param string|array $class Class or array of classes to remove
     * @return ExtDomparserDocument
     */
    public function removeClass($class);
}