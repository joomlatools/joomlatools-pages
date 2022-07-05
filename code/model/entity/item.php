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

    private $__model;

    public static function getInstance(KObjectConfigInterface $config, KObjectManagerInterface $manager)
    {
        if($config->entity)
        {
            $config->object_identifier = $config->entity;

            if(!$class = $manager->getClass($config->entity, false)) {
                $instance = new static($config);
            } else {
                $instance = new $class($config);
            }
        }
        else $instance = new static($config);

        return $instance;
    }

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        if($config->model) {
            $this->setModel($config->model);
        }

        $this->__internal_properties = KObjectConfig::unbox($config->internal_properties);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'model' => null,
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

        if($result instanceof Traversable) {
            $result = iterator_count($result) ? $result : $default;
        } elseif($result instanceof Countable) {
            $result = count($result) ?  $result : $default;
        } else {
            $result = !empty($result) ? $result : $default;
        }

        return $result;
    }

    public function set($name, $value, $modified = true)
    {
        return $this->setProperty($name, $value, $modified);
    }

    public function has($name)
    {
        return $this->hasProperty($name);
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

    public function getProperty($name)
    {
        $result = null;

        //Handle computed properties
        if(!empty($name))
        {
            $result = KObjectArray::offsetGet($name);

            $getter  = 'getProperty'.KStringInflector::camelize($name);
            $methods = $this->getMethods();

            if(isset($methods[$getter])) {
                $result = $this->$getter($result);
            }
        }

        return $result;
    }

    public function setProperty($name, $value, $modified = true)
    {
        if (!array_key_exists($name, $this->_data) || ($this->_data[$name] != $value))
        {
            //Call the setter if it exists
            $setter  = 'setProperty'.KStringInflector::camelize($name);
            $methods = $this->getMethods();

            if(isset($methods[$setter])) {
                $value = $this->$setter($value);
            }

            //Set the property value
            KObjectArray::offsetSet($name, $value);

            //Mark the property as modified
            if($modified || $this->isNew()) {
                $this->_modified[$name] = $name;
            }
        }

        return $this;
    }

    public function toArray()
    {
        $data = parent::toArray();

        $computed = $this->getComputedProperties();
        $internal = $this->getInteralProperties();

        //Remove internal properties
        $data = array_diff_key($data, array_flip($internal));

        ///Add none-internal computed properties
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

    public function setModel(ComPagesModelInterface $model)
    {
        $this->__model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->__model;
    }

    public function isConnected()
    {
        return (bool) $this->__model;
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