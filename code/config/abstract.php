<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesConfigAbstract extends KObject implements ComPagesConfigInterface
{
    public function get($option, $default = null)
    {
        $method = 'get'.KStringInflector::camelize($option);
        if(!method_exists($this, $method) ) {
            $value = $this->getConfig()->get($option, $default);
        } else {
            $value = $this->$method($default);
        }

        return $value;
    }

    public function getOptions()
    {
        return new ComPagesObjectConfig($this->getConfig());
    }

    final public function toArray()
    {
        return $this->getConfig()->toArray();
    }

    final public function __get($key)
    {
        return $this->get($key);
    }

    public function __call($method, $arguments)
    {
       if(strpos( $method , 'get' ) === 0)
       {
           if($option = substr($method, 3))
           {
               $option = KStringInflector::underscore($option);
               return $this->get($option);
           }
       }

       return parent::__call($method, $arguments);
    }
}