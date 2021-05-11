<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($object)
{
    if(is_object($object))
    {
        if(method_exists($object, 'toArray')) {
            $properties = $object->toArray();
        } elseif($object instanceof Traversable) {
            $properties = iterator_to_array($object);
        } else {
            $properties = get_object_vars($object);
        }
    }
    else $properties = $object;

    return $properties;
};