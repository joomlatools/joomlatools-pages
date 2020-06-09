<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityItem extends KModelEntityAbstract implements ComPagesModelEntityInterface
{
    use ComPagesObjectDebuggable;

    private $__internal_properties;

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

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__internal_properties = KObjectConfig::unbox($config->internal_properties);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'internal_properties' => [],
        ]);

        parent::_initialize($config);
    }

    public function getInteralProperties()
    {
        return $this->__internal_properties;
    }

    public function get($property, $default = null)
    {
        $result = $this->getProperty($property);

        if(empty($result)) {
            $result = $default;
        }

        return $result;
    }

    public function save()
    {
        if (!$this->isNew()) {
            $this->setStatus(self::STATUS_UPDATED);
        } else {
            $this->setStatus(self::STATUS_CREATED);
        }

        return true;
    }

    public function delete()
    {
        $this->setStatus(self::STATUS_DELETED);
        return true;
    }

    public function resetModified()
    {
        $this->_modified = array();
        return $this;
    }

    public function toArray()
    {
        $data = parent::toArray();

        $computed = $this->getComputedProperties();
        $internal = $this->getInteralProperties();

        //Remove internal properties
        $data = array_diff_key($data, array_flip($internal));

        //Add none-internal computed properties
        foreach(array_diff($computed, $internal) as $property) {
            $data[$property] = $this->{$property};
        }

        //Unpack config objects
        array_walk_recursive($data, function(&$value, $key)
        {
            if($value instanceof KObjectConfigInterface) {
                $value = KObjectConfig::unbox($value);
            }
        });

        return $data;
    }

    public function __call($method, $arguments)
    {
        $parts = KStringInflector::explode($method);

        //Check if a behavior is mixed
        if ($parts[0] == 'is' && isset($parts[1]))
        {
            if(!isset($this->_mixed_methods[$method])) {
                return false;
            }
        }

        return parent::__call($method, $arguments);
    }
}