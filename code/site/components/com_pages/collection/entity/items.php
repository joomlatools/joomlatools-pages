<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesCollectionEntityItems extends KModelEntityComposite implements JsonSerializable
{
    public function jsonSerialize()
    {
        $result = array();
        foreach ($this as $key => $entity) {
            $result[$key] = $entity->jsonSerialize();
        }

        return $result;
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }

    public function create(array $properties = array(), $status = null)
    {
        if($this->_prototypable)
        {
            if(!$this->_prototype instanceof KModelEntityInterface)
            {
                $identifier = $this->getIdentifier()->toArray();
                $identifier['path'] = array('collection', 'entity');
                $identifier['name'] = KStringInflector::singularize($this->getIdentifier()->name);

                //The entity default options
                $options = array(
                    'identity_key' => $this->getIdentityKey()
                );

                $this->_prototype = $this->getObject($identifier, $options);
            }

            $entity = clone $this->_prototype;

            $entity->setStatus($status);
            $entity->setProperties($properties, $entity->isNew());
        }
        else
        {
            $identifier = $this->getIdentifier()->toArray();
            $identifier['path'] = array('collection', 'entity');
            $identifier['name'] = KStringInflector::singularize($this->getIdentifier()->name);

            //The entity default options
            $options = array(
                'data'         => $properties,
                'status'       => $status,
                'identity_key' => $this->getIdentityKey()
            );

            $entity = $this->getObject($identifier, $options);
        }

        //Insert the entity into the collection
        $this->insert($entity);

        return $entity;
    }
}