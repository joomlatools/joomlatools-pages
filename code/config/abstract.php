<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesConfigAbstract extends KObject
{
    public function get($option, $default = null)
    {
        $method = 'get'.str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $option))));
        if(!method_exists($this, $method) ) {
            $value = $this->getConfig()->get($option, $default);
        } else {
            $value = $this->$method($default);
        }

        return $value;
    }

    public function toArray()
    {
        return KObjectConfig::unbox($this->getConfig());
    }

    final public function __get($key)
    {
        return $this->get($key);
    }
}