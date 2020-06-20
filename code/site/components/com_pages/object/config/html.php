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
        $data = ComPagesDomDocument::fromString($string, true);
        return $object ? $this->merge($data) : $data;
    }

    public function toString()
    {
        return ComPagesDomDocument::fromArray($this->toArray(), true)->toString();
    }

    public function fromArray($array, $object = true)
    {
        if($array instanceof KObjectConfigInterface) {
            $array = $array->toArray();
        }

        $document = ComPagesDomDocument::fromArray($array, true);
        return $object ? $this->merge($document) : $document;
    }
}
