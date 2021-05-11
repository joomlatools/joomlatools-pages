<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityItems extends KModelEntityComposite implements JsonSerializable, ComPagesModelEntityInterface
{
    use ComPagesObjectDebuggable;

    public static function getInstance(KObjectConfigInterface $config, KObjectManagerInterface $manager)
    {
        if($config->entity)
        {
            $config->object_identifier = $config->entity;

            if(!$class = $manager->getClass($config->entity, false)) {
                $instance = new self($config);
            } else {
                $instance = new $class($config);
            }
        }
        else $instance = new self($config);

        return $instance;
    }

    public function get($property, $default = null)
    {
        $result = $default;
        if($entity = $this->getIterator()->current()) {
            $result = $entity->get($property, $default);
        }

        return $result;
    }

    public function has($property)
    {
        $result = false;
        if($entity = $this->getIterator()->current()) {
            $result = $entity->has($property);
        }

        return $result;
    }

    public function jsonSerialize()
    {
        $result = array();
        foreach ($this as $key => $entity) {
            $result[$key] = $entity->jsonSerialize();
        }

        return $result;
    }

    public function create(array $properties = array(), $status = null)
    {
        if($this->_prototypable)
        {
            if(!$this->_prototype instanceof KModelEntityInterface)
            {
                $identifier = $this->getIdentifier()->toArray();
                $identifier['name'] = KStringInflector::singularize($identifier['name']);

                //The entity default options
                $options = array(
                    'identity_key' => $this->getIdentityKey(),
                    'entity'       => $this->getIdentifier($identifier)
                );

                //Delegate entity instantiation
                $this->_prototype = $this->getObject('com:pages.model.entity.item', $options);
            }

            $entity = clone $this->_prototype;

            $entity->setStatus($status);
            $entity->setProperties($properties, $entity->isNew());
        }
        else
        {
            $identifier = $this->getIdentifier()->toArray();
            $identifier['name'] = KStringInflector::singularize($identifier['name']);

            //The entity default options
            $options = array(
                'data'         => $properties,
                'status'       => $status,
                'identity_key' => $this->getIdentityKey(),
                'entity'       => $this->getIdentifier($identifier)
            );

            //Delegate entity instantiation
            $entity = $this->getObject('com:pages.model.entity.item', $options);
        }

        //Insert the entity into the collection
        $this->insert($entity);

        return $entity;
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

        }
        else $result = KObject::__call($method, $arguments);

        return $result;
    }
}