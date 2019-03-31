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

    public function filter($key, $value = null)
    {
        $data = $this->toArray();

        //Filter the array
        $data = array_filter($data, function($v) use ($key, $value)
        {
            if($value !== null) {
                return (isset($v[$key]) && $v[$key] === $value);
            } else {
                return isset($v[$key]);
            }
        });

        //Reset the numeric keys
        if (is_numeric(key($data))) {
            $data = array_values($data);
        }

        //Do no return an array if we only found one result
        if(count($data) == 1) {
           $data = $data[0];
        }

        return new self($data);
    }

    public function toString()
    {
        // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be embedded into HTML.
        $data = json_encode($this->toArray(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (JSON_ERROR_NONE !== json_last_error())
        {
            throw new InvalidArgumentException(
                'Cannot encode data to JSON string: ' . json_last_error_msg()
            );
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