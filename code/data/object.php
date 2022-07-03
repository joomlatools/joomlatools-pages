<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDataObject extends ComPagesObjectConfig
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

    public function sort($key, $order = 'asc')
    {
        if($key)
        {
            $data = $this->toArray();

            usort($data, function($first, $second) use($key)
            {
                $sorting = 0;

                $first_value  = $first[$key];
                $second_value = $second[$key];

                if($first_value > $second_value) {
                    $sorting = 1;
                } elseif ($first_value < $second_value) {
                    $sorting = -1;
                }

                return $sorting;
            });

            switch($order)
            {
                case 'desc':
                case 'descending':
                    $data = array_reverse($data);
                    break;

                case 'random':
                case 'shuffle':
                    shuffle($data);
                    break;
            }

            return new self($data);
        }

        return $this;
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
        if($key)
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

            return is_array($data) ? new self($data) : $data;

        }

        return $this;
    }

    public function find($key, $depth = 0)
    {
        if($key)
        {
            $result = [];
            $data   = $this->toArray();

            $array    = new RecursiveArrayIterator($data);
            $iterator = new RecursiveIteratorIterator($array, \RecursiveIteratorIterator::SELF_FIRST);

            //Set the max dept, -1 for full depth
            $iterator->setMaxDepth($depth);

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


            //Do no return an array if we only found one result
            if(count($result) == 1  && isset($result[0])) {
                $result = $result[0];
            }


            return is_array($result) ? new self($result) : $result;
        }

        return $this;
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

    public function toJson()
    {
        $html = new ComPagesObjectConfigJson($this);
        return $html;
    }
}