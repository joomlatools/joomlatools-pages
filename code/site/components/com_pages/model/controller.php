<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelController extends KModelAbstract implements ComPagesModelInterface
{
    protected $_controller;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_controller = $config->controller;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'controller' => '',
        ));

        parent::_initialize($config);
    }

    public function getController()
    {
        if(!($this->_controller instanceof KControllerInterface))
        {
            //Make sure we have a controller identifier
            if(!($this->_controller instanceof KObjectIdentifier))
            {
                if(is_string($this->_controller) && strpos($this->_controller, '.') === false )
                {
                    $identifier         = $this->getIdentifier()->toArray();
                    $identifier['path'] = array('controller');
                    $identifier['name'] = KStringInflector::underscore($this->_controller);

                    $identifier = $this->getIdentifier($identifier);
                }
                else  $identifier = $this->getIdentifier($this->_controller);

                if($identifier->path[0] != 'controller') {
                    throw new UnexpectedValueException('Identifier: '.$identifier.' is not a controller identifier');
                }

                $this->_controller = $identifier;
            }

            $this->_controller = $this->getObject($this->_controller);

            if(!$this->_controller instanceof KControllerModellable)
            {
                throw new UnexpectedValueException(
                    'Controller: '.get_class($this->_controller).' does not implement KControllerModellable'
                );
            }
        }

        return $this->_controller;
    }

    public function setState(array $values)
    {
        $this->getController()->getModel()->setState($values);
        return $this;
    }

    public function getState()
    {
        return $this->getController()->getModel()->getState();
    }

    public function getType()
    {
        $name    = $this->getController()->getIdentifier()->getName();
        $package = $this->getController()->getIdentifier()->getPackage();

        return sprintf('%s-%s', $package, $name);
    }

    public function getIdentityKey()
    {
        $model = $this->getController()->getModel();

        if(!$model instanceof ComPagesModelInterface) {
            $key = $model->fetch()->getIdentityKey();
        } else {
            $key = $model->getIdentityKey();
        }

        return $key;
    }

    public function getPrimaryKey()
    {
        $model = $this->getController()->getModel();

        if(!$model instanceof ComPagesModelInterface) {
            $key = $model->fetch()->getIdentityKey();
        } else {
            $key = $model->getPrimaryKey();
        }

        return (array) $key;
    }

    public function getValidator()
    {
        $validator = null;
        $model = $this->getController()->getModel();

        if($model instanceof KModelDatabase)
        {
            if($modified = $model->getTable()->getSchema()->modified) {
                $validator = hash('crc32b', $modified);
            }
        }

        if($model instanceof ComPagesModelInterface) {
            $validator = $model->getValidator();
        }

        return $validator;
    }

    public function isAtomic()
    {
        $atomic = true;

        if(!$this->getState()->isUnique())
        {
            foreach($this->getPrimaryKey() as $key)
            {
                if(!$this->getState()->get($key))
                {
                    $atomic = false;
                    break;
                }
            }
        }

        return $atomic;
    }

    final public function persist()
    {
        if(isset($this->_entity))
        {
            $context = $this->getContext();
            $context->entity  = $this->_entity;

            if ($this->invokeCommand('before.persist', $context) !== false)
            {
                $context->result = $this->_actionPersist($context);
                $this->invokeCommand('after.persist', $context);
            }
        }

        return $context->result;
    }

    protected function _actionFetch(KModelContext $context)
    {
        if ($this->getState()->isUnique()) {
            $result =  $this->getController()->read();
        } else {
            $result =  $this->getController()->browse();
        }

        return $result;
    }

    protected function _actionCount(KModelContext $context)
    {
        return $this->getController()->count();
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->getController()->getModel()->reset();
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result = self::PERSIST_SUCCESS;
        
        if($this->getController()->getModel() instanceof ComPagesModelInterface) {
            $result = $this->getController()->getModel()->persist();
        }

        return $result;
    }
}