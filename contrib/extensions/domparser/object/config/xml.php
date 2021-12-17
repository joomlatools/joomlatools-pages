<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserObjectConfigXml extends KObjectConfigFormat
{
    protected static $_format = 'application/xml';

    public function fromString($string, $object = true)
    {
        $data = ComPagesDataDocumentFactory::fromString($string, true)->toArray();
        return $object ? $this->merge($data) : $data;
    }

    public function toString()
    {
        return ComPagesDataDocumentFactory::fromArray($this->toArray(), true)->toString();
    }
}