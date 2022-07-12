<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserObjectConfigHtml extends ExtDomparserObjectConfigXml
{
    protected static $_format = 'text/html';

    public function fromString($string, $object = true)
    {
        $data = ExtDomparserDocumentFactory::fromString($string)->toArray();
        return $object ? $this->merge($data) : $data;
    }

    public function toString()
    {
        return ExtDomparserDocumentFactory::fromArray($this->toArray())->toString();
    }
}