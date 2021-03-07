<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesObjectConfig extends KObjectConfig implements JsonSerializable
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

    public function toString()
    {
        $json = new ComPagesObjectConfigJson($this);
        return $json->toString();
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function __debugInfo()
    {
        return self::unbox($this);
    }

    final public function __toString()
    {
        $result = '';

        //Not allowed to throw exceptions in __toString() See : https://bugs.php.net/bug.php?id=53648
        try {
            $result = $this->toString();
        } catch (Exception $e) {
            trigger_error('ComPagesObjectConfig::__toString exception: '. (string) $e, E_USER_ERROR);
        }

        return $result;
    }
}