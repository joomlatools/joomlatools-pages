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
    protected $_data;
    protected $_path;
    protected $_base_path;
    protected $_identity_key_length;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_path      = $config->path;
        $this->_base_path = $config->base_path;

        $this->_identity_key_length = $config->identity_key_length;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'persistable'         => true,
            'identity_key'        => null,
            'identity_key_length' =>  4,
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

    public function createIdentity()
    {
        return bin2hex(random_bytes($this->_identity_key_length));
    }

    public function getHash($refresh = false)
    {
        $hahs = null;
        $path = $this->getPath($this->getState()->getValues());

        if(file_exists($path)) {
            $hash = hash('crc32b', filemtime($path));
        }

        return $hash;
    }

    public function fetchData($count = false)
    {
        if(!isset($this->_data))
        {
            $this->_data = array();
            $path        = $this->getPath($this->getState()->getValues());

            //Only fetch data if the file exists
            if(file_exists($path)) {
                $this->_data = $this->getObject('object.config.factory')->fromFile($path, false);
            }
        }

       return $this->_data;
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->_data = null;

        parent::_actionReset($context);
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result       = true;
        $identity_key = $this->getIdentityKey();

        $data = $context->data;

        if($identity_key)
        {
            $keys = array_column($data, $identity_key);
            $data = array_combine($keys, $data);
        }

        foreach($context->entity as $entity)
        {
            $key    = $entity->getProperty($identity_key);
            $values = $entity->toArray();

            if($entity->getStatus() == $entity::STATUS_CREATED)
            {
                //Only add none existing entities
                if(!isset($data[$key]))
                {
                    //Prevent duplicate unique values
                    foreach($context->state  as $state)
                    {
                        $name = $state->name;

                        if($state->unique && array_search($entity->$name, array_column($data, $name)) !== false)
                        {
                            throw new ComPagesModelExceptionConflict(
                                sprintf("Duplicate entry '%s' for key '%s'", $entity->$name, $name)
                            );
                        }
                    }

                    if($identity_key)
                    {
                        $identity = $this->createIdentity();
                        $data[$identity] = [$identity_key => $identity] + $values;

                        //Set the identity in the entity
                        $entity->setProperty($identity_key, $identity, false);
                    }

                    $result = self::PERSIST_SUCCESS;
                }
                else $result = self::PERSIST_FAILURE;
            }

            if($entity->getStatus() == $entity::STATUS_UPDATED)
            {
                //Only update existing entities
                if(isset($data[$key]))
                {
                    //Do not update is no data has changed
                    if(array_diff_assoc($values, $data[$key]))
                    {
                        unset($data[$key]);

                        //Prevent duplicate unique values
                        foreach($context->state  as $state)
                        {
                            $name = $state->name;

                            if($state->unique && array_search($entity->$name, array_column($data, $name)) !== false)
                            {
                                throw new ComPagesModelExceptionConflict(
                                    sprintf("Duplicate entry '%s' for key '%s'", $entity->$name, $state->name)
                                );
                            }
                        }

                        $data[$key] = $values;
                        $result = self::PERSIST_SUCCESS;
                    }
                    else $result = self::PERSIST_NOCHANGE;

                }
                else $result = self::PERSIST_FAILURE;
            }

            if($entity->getStatus() == $entity::STATUS_DELETED)
            {
                //Only delete existing entities
                if(isset($data[$key]))
                {
                    unset($data[$key]);
                    $result = self::PERSIST_SUCCESS;
                }
                else $result = self::PERSIST_FAILURE;
            }

            //Reset the entity modified state
            if($result === self::PERSIST_SUCCESS) {
                $entity->resetModified();
            }

            if($result === self::PERSIST_FAILURE) {
                break;
            }
        }

        if($result === self::PERSIST_SUCCESS)
        {
            $data = array_values($data);

            $path = $this->getPath($this->getState()->getValues());
            $this->getObject('object.config.factory')->toFile($path, $data);
        }

        return $result;
    }
}