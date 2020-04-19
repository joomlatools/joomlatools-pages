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
        if(count($data) == 1 && isset($data[0]) && !is_array($data[0])) {
            $data = $data[0];
        } else {
            $data = new self($data);
        }

        return $data;
    }


    public function find($key)
    {
        $data = $this->toArray();

        $array    = new RecursiveArrayIterator($data);
        $iterator = new RecursiveIteratorIterator($array, \RecursiveIteratorIterator::SELF_FIRST);

        $result = array();
        foreach ($iterator as $k => $v)
        {
            if($key === $k)
            {
                if(is_array($v) && is_numeric(key($v))) {
                    $result = array_merge($result, $v);
                } else {
                    $result[] = $v;
                }
            }
        }

        //Do no return an array if we only found a single scalar result
        if(count($result) == 1 && isset($result[0]) && !is_array($result[0])) {
            $result = $result[0];
        } else {
            $result = new self($result);
        }

        return $result;
    }

    public function toString()
    {
        $data = $this->toArray();

        if(is_array($data))
        {
            if(!isset($data['@value'])) {
                $data = $this->toJson()->toString();
            } else {
                $data = $data['@value'];
            }
        }

        return $data;
    }

    public function toHtml()
    {
        $html = new ComPagesObjectConfigHtml($this);
        return $html->toDom();
    }

    public function toXml()
    {
        $html = new ComPagesObjectConfigXml($this);
        return $html->toDom();
    }

    public function toJson()
    {
        $html = new ComPagesObjectConfigJson($this);
        return $html;
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