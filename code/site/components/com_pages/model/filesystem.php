<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelFilesystem extends ComPagesModelCollection
{
    protected $_path;

    protected $_base_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_path      = $config->path;
        $this->_base_path = $config->base_path;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key' => 'id',
            'path'         => '',
            'base_path'    =>  $this->getObject('com:pages.config')->getSitePath(),
        ]);

        parent::_initialize($config);
    }

    public function getPath(array $variables = array())
    {
        $path = (string) KHttpUrl::fromTemplate($this->_path, $variables);
        $path = $path[0] != '/' ?  $this->_base_path.'/'.$path : $path;

        return $path;
    }

    public function setState(array $values)
    {
        //Automatically create states that don't exist yet
        foreach($values as $name => $value)
        {
            if(!$this->getState()->has($name)) {
                $this->getState()->insert($name, 'string');
            }
        }

        return parent::setState($values);
    }

    public function fetchData($count = false)
    {
        $data = array();
        $path = $this->getPath($this->getState()->getValues());

        //Only fetch data if the file exists
        if(file_exists($path)) {
            $data = $this->getObject('object.config.factory')->fromFile($path, false);
        }

       return $data;
    }

    public function filterData($data)
    {
        $identity_key = $this->getIdentityKey();

        $result = array();
        foreach($data as $key => $value)
        {
            if(isset($value[$identity_key])) {
                throw new RuntimeException('Identity key: "'.$identity_key.'" already exists in item with offset '.($key + 1));
            }

            //Add identity key to value for lookups
            $value[$identity_key] = $key + 1;

            //Store filtered value
            $result[] = $value;
        }

        return parent::filterData($result);
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result       = true;
        $identity_key = $this->getIdentityKey();

        foreach($context->entity as $entity)
        {
            $key    = $entity->getProperty($identity_key) - 1;
            $data   = $context->data;
            $values = $entity->toArray();

            //Remove the identity key, we don't want to store it.
            unset($values[$identity_key]);

            if($entity->getStatus() == $entity::STATUS_CREATED)
            {
                //Only add none existing entities
                if(!isset($data[$key]))
                {
                    //Prevent duplicate unique values
                    foreach($context->state->getNames(true) as $name)
                    {
                        if(array_search($entity->$name, array_column($data, $name)) !== false) {
                           $result = false; break;
                        }
                    }

                    if($result) {
                        $data[] = $values;
                    }
                }
                else $result = false;
            }

            if($entity->getStatus() == $entity::STATUS_UPDATED)
            {
                //Only update existing entities
                if(isset($data[$key]))
                {
                    unset($data[$key]);

                    //Prevent duplicate unique values
                    foreach($context->state->getNames(true) as $name)
                    {
                        if(array_search($entity->$name, array_column($data, $name)) !== false) {
                            $result = false; break;
                        }
                    }

                    if($result) {
                        $data[$key] = $values;
                    }
                }
                else $result = false;
            }

            if($entity->getStatus() == $entity::STATUS_DELETED)
            {
                //Only delete existing entities
                if(isset($data[$key])) {
                    unset($data[$key]);
                } else {
                    $result = false;
                }
            }

            //Reset the entity modified state
            if($result == true) {
                $entity->resetModified();
            } else {
                break;
            }
        }

        if($result === true)
        {
            $path = $this->getPath($this->getState()->getValues());
            $this->getObject('object.config.factory')->toFile($path, $data);
        }

        return $result;
    }
}