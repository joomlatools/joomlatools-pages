<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelDecorator extends KModelAbstract implements ComPagesModelInterface
{
    private $__persistable;
    private $__delegate;
    private $__name;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__delegate = $config->delegate;

        //Set if the collection is persistable
        $this->__persistable = $config->persistable;

        //Set the name
        $this->__name = $config->name;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'persistable' => true,
            'delegate'    => '',
            'name'        => '', //the collection name used to generate this model
        ));

        parent::_initialize($config);
    }

    public function getDelegate()
    {
        if(!$this->__delegate instanceof KControllerModellable && !$this->__delegate instanceof KModelInterface)
        {
            //Make sure we have a controller identifier
            if(!($this->__delegate instanceof KObjectIdentifier))
            {
                $identifier = $this->getIdentifier($this->__delegate);

                if($identifier->path[0] != 'controller' && $identifier->path[0] != 'model')
                {
                    throw new UnexpectedValueException(
                        'Identifier: '.$identifier.' is not a controller or model identifier'
                    );
                }

                $this->__delegate = $identifier;
            }

            $this->__delegate = $this->getObject($this->__delegate);

            if(!$this->__delegate instanceof KControllerModellable && !$this->__delegate instanceof KModelInterface)
            {
                throw new UnexpectedValueException(
                    'Decorator: '.get_class($this->__delegate).' does not implement KControllerModellable or KModelInterface'
                );
            }
        }

        return $this->__delegate;
    }

    public function setState(array $values)
    {
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)  {
            $delegate->getModel()->setState($values);
        } else {
            $delegate->setState($values);
        }

        return $this;
    }

    public function getState()
    {
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)  {
            $state = $delegate->getModel()->getState();
        } else {
            $state = $delegate->getState();
        }

        return $state;
    }

    public function getType()
    {
        $name    = $this->getDelegate()->getIdentifier()->getName();
        $package = $this->getDelegate()->getIdentifier()->getPackage();

        return sprintf('%s-%s', $package, $name);
    }

    public function getName()
    {
        return $this->__name;
    }

    public function getIdentityKey()
    {
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)  {
            $model = $delegate->getModel();
        } else {
            $model = $delegate;
        }

        if(!$model instanceof ComPagesModelInterface) {
            $key = $model->fetch()->getIdentityKey();
        } else {
            $key = $model->getIdentityKey();
        }

        return $key;
    }

    public function getPrimaryKey()
    {
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)  {
            $model = $delegate->getModel();
        } else {
            $model = $delegate;
        }

        if(!$model instanceof ComPagesModelInterface) {
            $key = $model->fetch()->getIdentityKey();
        } else {
            $key = $model->getPrimaryKey();
        }

        return (array) $key;
    }

    public function getHash($refresh = false)
    {
        $hash     = null;
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)  {
            $model = $delegate->getModel();
        } else {
            $model = $delegate;
        }

        if($model instanceof KModelDatabase)
        {
            if($modified = $model->getTable()->getSchema()->modified) {
                $hash = hash('crc32b', $modified);
            }
        }

        if($model instanceof ComPagesModelInterface) {
            $hash = $model->getHash($refresh);
        }

        return $hash;
    }

    public function getHashState()
    {
        $hash     = null;
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)  {
            $model = $delegate->getModel();
        } else {
            $model = $delegate;
        }

        if($model instanceof KModelDatabase)
        {
            $states = array();
            foreach($model->getState() as $state)
            {
                if(($state->required === true || $state->unique === true || $state->internal === true) && !is_null($state->value)) {
                    $states[$state->name] = KObjectConfig::unbox($state->value);
                }
            }
        }

        if($model instanceof ComPagesModelInterface) {
            $states = $model->getHashState();
        }

        return $states;
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
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)
        {
            if ($this->getState()->isUnique()) {
                $result =  $this->getDelegate()->read();
            } else {
                $result =  $this->getDelegate()->browse();
            }
        }
        else $result = $this->getDelegate()->fetch();

        return $result;
    }

    protected function _actionCount(KModelContext $context)
    {
        return $this->getDelegate()->count();
    }

    protected function _actionReset(KModelContext $context)
    {
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)  {
            $delegate->getModel()->reset();
        } else {
            $delegate->reset();
        }
    }

    protected function _beforePersist(KModelContext $context)
    {
        return $this->isPersistable();
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result   = self::PERSIST_SUCCESS;
        $delegate = $this->getDelegate();

        if($delegate instanceof KControllerModellable)  {
            $model = $delegate->getModel();
        } else {
            $model = $delegate;
        }

        if($model instanceof ComPagesModelInterface) {
            $result = $model->persist();
        }

        return $result;
    }

    public function isPersistable()
    {
        if($result = $this->__persistable)
        {
            $delegate = $this->getDelegate();

            if($delegate instanceof KControllerModellable)  {
                $model = $delegate->getModel();
            } else {
                $model = $delegate;
            }

            if(!$model instanceof ComPagesModelInterface) {
                $result = false;
            }
        }

        return $result;
    }
}