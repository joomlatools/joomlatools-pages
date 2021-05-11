<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesObjectConfigHtml extends ComPagesObjectConfigXml
{
    protected static $_format = 'text/html';

    public function fromString($string, $object = true)
    {
        $data = array();

        if(!empty($string))
        {
            $dom = new DOMDocumentHtml('1.0', 'UTF-8');
            libxml_use_internal_errors(true);

            $dom->normalizeDocument();
            $dom->preserveWhiteSpace = false;

            if($dom->loadHtml($string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS) === false) {
                throw new DomainException('Cannot parse HTML string');
            }

            libxml_use_internal_errors(false);

            $data = $this->_domToArray($dom);
        }

        return $object ? $this->merge($data) : $data;
    }

    public function toString()
    {
        return $this->toDom()->saveHTML();
    }

    public function toDom()
    {
        $data   = $this->toArray();

        $dom = new DOMDocumentHtml('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $this->_arrayToDom($dom, $dom, $data);

        return $dom;
    }
}

class DomDocumentHtml extends DOMDocument
{
    private $__xpath;

    public function __toString() {
        return parent::safeHTML();
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