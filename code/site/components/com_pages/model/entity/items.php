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
            if(!$class = $manager->getClass($config->entity, false))
            {
                $config->object_identifier = $config->entity;
                $instance = new static($config);
            }
            else $instance = new $class($config);
        }
        else $instance = new static($config);

        return $instance;
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
                $this->_prototype = $this->getObject('com://site/pages.model.entity.item', $options);
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
            $entity = $this->getObject('com://site/pages.model.entity.item', $options);
        }

        //Insert the entity into the collection
        $this->insert($entity);

        return $entity;
    }
}