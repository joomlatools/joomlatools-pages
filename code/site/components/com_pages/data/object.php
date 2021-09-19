<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDataObject extends KObjectConfig implements JsonSerializable
{
    public function shuffle()
    {
        $data = $this->toArray();
        shuffle($data);

        return new static($data);
    }

    public function slice($offset, $length = NULL)
    {
        $data = $this->toArray();
        $data = array_slice($data, $offset, $length);

        return new static($data);
    }

    public function reverse()
    {
        $data = $this->toArray();
        array_reverse($data);

        return new static($data);
    }

    public function flatten($key_as_property = null)
    {
        $data = array();

        foreach( $this->toArray() as $key => $values)
        {
            if(is_array($values))
            {
                if (is_string($key_as_property) && !is_numeric($key))
                {
                    // Keep current key as a property of the data object
                    foreach ($values as &$value) {
                        $value[$key_as_property] = $key;
                    }
                }

                $data = array_merge($data, $values);
            }
            else $data[] = $values;
        }

        return new static($data);
    }

    public function filter($key, $value = null, $exclude = false)
    {
        $data = $this->toArray();

        if(isset($data[$key])) {
            $data = array($data);
        }

        //Filter the array
        $data = array_filter($data, function($v) use ($key, $value, $exclude)
        {
            if($value !== null && isset($v[$key]))
            {
                if(is_array($value)  && is_array($v[$key]))
                {
                    if($exclude) {
                        return (bool) array_diff_assoc($value, $v[$key]);
                    } else {
                        return (bool) !array_diff_assoc($value, $v[$key]);
                    }

                }
                else
                {
                    if($exclude) {
                        return ($v[$key] !== $value);
                    } else {
                        return ($v[$key] === $value);
                    }
                }
            }
            else
            {
                if($exclude) {
                    return !isset($v[$key]);
                } else {
                    return isset($v[$key]);
                }

            }
        });

        //Reset the numeric keys
        if (is_numeric(key($data))) {
            $data = array_values($data);
        }

        //Do no return an array if we only found a single scalar result
        if(count($data) == 1 && isset($data[0])) {
            $data = $data[0];
        }

        return is_array($data) ? new static($data) : $data;
    }

    public function toString()
    {
        $data = $this->toArray();

        if(is_array($data))
        {
            if(!isset($data['@value']))
            {
                $json = new ComPagesObjectConfigJson($this);
                $data = $json->toString();
            }
            else $data = $data['@value'];
        }

        return $data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function __debugInfo()
    {
        return static::unbox($this);
    }

    /**
     * Allow PHP casting of this object
     *
     * @return string
     */
    final public function __toString()
    {
        $result = '';

        //Not allowed to throw exceptions in __toString() See : https://bugs.php.net/bug.php?id=53648
        try {
            $result = $this->toString();
        } catch (Exception $e) {
            trigger_error('KObjectConfigFormat::__toString exception: '. (string) $e, E_USER_ERROR);
        }

        return $result;
    }
}