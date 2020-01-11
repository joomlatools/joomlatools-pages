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
    public function get($name, $default = null)
    {
        $result = $default;
        $path   = explode('/', $name);

        if($result = parent::get(array_shift($path)))
        {
            foreach($path as $name)
            {
                if($result instanceof KObjectConfigInterface && $result->has($name)) {
                    $result = $result->get($name);
                } else {
                    $result = $default;
                    break;
                }
            }
        }

        return $result;
    }

    public function has($name)
    {
        return (bool) $this->get($name);
    }

    public function shuffle()
    {
        $data = $this->toArray();
        shuffle($data);

        return new self($data);
    }

    public function slice($offset, $length = NULL)
    {
        $data = $this->toArray();
        $data = array_slice($data, $offset, $length);

        return new self($data);
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

        return new self($data);
    }

    public function filter($key, $value = null, $strict = false)
    {
        $data = $this->toArray();

        if(isset($data[$key])) {
            $data = array($data);
        }

        //Filter the array
        $data = array_filter($data, function($v) use ($key, $value, $strict)
        {
            if($value !== null)
            {
                if(!$strict && is_array($value) && is_array($v[$key])) {
                    return (bool) !array_diff_assoc($value, $v[$key]);
                } else {
                    return (isset($v[$key]) && $v[$key] === $value);
                }
            }
            else return isset($v[$key]);
        });

        //Reset the numeric keys
        if (is_numeric(key($data))) {
            $data = array_values($data);
        }

        //Do no return an array if we only found one result
        if(count($data) == 1) {
            $data = $data[0];
        }

        return is_array($data) ? new self($data) : $data;
    }

    public function find($key)
    {
        $data = $this->toArray();

        $array    = new RecursiveArrayIterator($data);
        $iterator = new RecursiveIteratorIterator($array, \RecursiveIteratorIterator::SELF_FIRST);

        $result = array();
        foreach ($iterator as $k => $v)
        {
            if($key === $k) {
                $result[] = $v;
            }
        }

        //Reset the numeric keys
        if (is_numeric(key($result))) {
            $data = array_values($result);
        }

        //Do no return an array if we only found one result
        if(count($result) == 1) {
            $result = $result[0];
        }


        return is_array($result) ? new self($result) : $result;
    }

    public function toString()
    {
        $data = $this->toArray();

        if(is_array($data))
        {
            if(!isset($data['@value']))
            {
                // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be embedded into HTML.
                $data = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                if (JSON_ERROR_NONE !== json_last_error())
                {
                    throw new InvalidArgumentException(
                        'Cannot encode data to JSON string: ' . json_last_error_msg()
                    );
                }
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
        return self::unbox($this);
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
