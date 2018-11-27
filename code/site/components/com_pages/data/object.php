<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDataObject extends KObjectConfig
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

    public function __debugInfo()
    {
        return self::unbox($this);
    }
}