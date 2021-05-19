<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

trait ComPagesObjectDebuggable
{
    public function __debugInfo()
    {
        if($this instanceof Traversable || method_exists($this, 'toArray'))
        {
            if(method_exists($this, 'toArray')) {
                $properties = $this->toArray();
            } else {
                $properties = iterator_to_array($this->getIterator());
            }
        }
        else $properties= get_object_vars($this);

        array_walk_recursive($properties, function(&$property, $key)
        {
            if(is_object($property))
            {
                //Call __debugInfo and use result
                if(method_exists($property, '__debugInfo'))
                {
                    ob_start();
                    var_dump($property);
                    $debug_info = ob_get_contents();
                    ob_end_clean();

                    $property = $debug_info;
                }
                //Cast to string if possible
                elseif(method_exists($property, '__toString')){
                    $property = (string) $property;
                }
                //Object identifier
                elseif($property instanceof KObjectInterface)
                {
                    $identifier = (string)$property->getIdentifier();
                    $property = $identifier . ' :: (' . get_class($property) . ')';

                }
                //Class name
                else $property = get_class($property);
            }
        });

        return $properties;
    }
}

