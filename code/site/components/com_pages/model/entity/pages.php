<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityPages extends KModelEntityComposite implements JsonSerializable
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key' => 'path',
            'prototypable' => false
        ]);

        parent::_initialize($config);
    }

    public function jsonSerialize()
    {
        $result = array();
        foreach ($this as $key => $entity) {
            $result[$key] = $entity->jsonSerialize();
        }

        return $result;
    }

    public function get($property, $default)
    {
        if($this->hasProperty($property)) {
            $result = $this->getProperty($property);
        } else {
            $result = $default;
        }
    }

    public function __call($method, $arguments)
    {
        $result = null;

        $methods = $this->getMethods();
        if(!isset($methods[$method]))
        {
            //Forward the call to the entity
            if($entity = parent::getIterator()->current()) {
                $result = $entity->__call($method, $arguments);
            }

        } else $result = KObject::__call($method, $arguments);

        return $result;
    }
}