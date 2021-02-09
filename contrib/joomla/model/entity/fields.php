<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaModelEntityFields extends ComPagesModelEntityItems
{
    public function get($property, $default = null)
    {
        if($item = $this->find($property)) {
            $result = $item;
        } else {
            $result = $default;
        }

        return $result;
    }

    public function has($property)
    {
        if($item = $this->find($property)) {
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    public function getProperty($name)
    {
        $result = null;

        if($entity = $this->get($name)) {
            $result = $entity;
        }

        return $result;
    }

    public function hasProperty($name)
    {
        $result = false;
        if($entity = $this->get($name)) {
            $result = $entity->hasProperty($name);
        }

        return $result;
    }
}
