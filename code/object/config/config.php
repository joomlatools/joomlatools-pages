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
        if(!is_array($name)) {
            $segments = explode('/', $name);
        } else {
            $segments = $name;
        }

        $result = parent::get(array_shift($segments), $default);

        if(!empty($segments) && $result instanceof KObjectConfigInterface) {
            $result = $result->get($segments, $default);
        }

        return $result;
    }

    public function has($name)
    {
        return (bool) ($this->get($name) !== null);
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

    final public function __toString()
    {
        return $this->toString();
    }

    public function __debugInfo()
    {
        return self::unbox($this);
    }
}