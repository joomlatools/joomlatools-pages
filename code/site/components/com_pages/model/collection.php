<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesModelCollection extends KModelAbstract implements ComPagesModelInterface, ComPagesModelFilterable
{
    private $__data;
    private $__type;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Insert the identity_key
        if($config->identity_key) {
            $this->getState()->insert($config->identity_key, 'url', null, true);
        }

        //Setup callbacks
        $this->addCommandCallback('before.fetch'  , '_prepareContext');
        $this->addCommandCallback('before.count'  , '_prepareContext');
        $this->addCommandCallback('before.persist', '_prepareContext');

        //Set the type
        $this->__type = $config->type;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'entity' => $this->getIdentifier()->getName(),
            'type'             =>  '',
            'identity_key'     => null,
            'behaviors'   => [
                'com://site/pages.model.behavior.paginatable',
                'com://site/pages.model.behavior.sortable',
                'com://site/pages.model.behavior.searchable',
                'com://site/pages.model.behavior.sparsable'
            ],
        ]);

        parent::_initialize($config);
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

    public function getType()
    {
        return $this->__type;
    }

    public function getIdentityKey()
    {
        return $this->_identity_key;
    }

    public function getPrimaryKey()
    {
        if(!$this->getIdentityKey())
        {
            $keys = array();
            foreach ($this->getState() as $name => $state)
            {
                //Unique values cannot be null or an empty string
                if($state->unique)
                {
                    foreach($state->required as $required) {
                        $keys[] = $required;
                    }

                    $keys[] = $state->name;
                }
            }
        }
        else $keys = (array) $this->getIdentityKey();

        return (array) $keys;
    }

    public function getLastModified()
    {
        return null;
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

    public function fetchData($count = false)
    {
        return array();
    }

    public function filterData($data)
    {
        $state = $this->getState();

        $result = array();
        foreach($data as $key => $item)
        {
            $filtered = $this->filterItem($item, $state);

            //Do not include the item if filtering returns empty array, null, or false
            if(!empty($filtered))
            {
                if(is_array($filtered)) {
                    $result[$key] = $filtered;
                } else {
                    $result[$key] = $item;
                }
            }
        }

        return $result;
    }

    public function filterItem($item, KModelStateInterface $state)
    {
        if($this->isAtomic())
        {
            foreach($state->getValues(true) as $key => $value)
            {
                if(isset($item[$key]) && !in_array($item[$key], (array) $value)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function _prepareContext(KModelContext $context)
    {
        if(!$this->__data) {
            $this->__data = $this->fetchData($context->getName() == 'before.count');
        }

        $context->data = $this->__data;
    }

    protected function _actionFetch(KModelContext $context)
    {
        $data     = $this->filterData($context->data);
        $entities = $this->create($data);

        //Mark the entities as fetched
        foreach($entities as $key => $entity)
        {
           $entity->setStatus(ComPagesModelEntityItem::STATUS_FETCHED);
           $entity->resetModified();
        }

        return $entities;
    }

    protected function _actionCreate(KModelContext $context)
    {
        $data = KModelContext::unbox($context->entity);

        $identifier = $this->getIdentifier()->toArray();
        $identifier['path'] = ['model', 'entity'];
        $identifier['name'] = KStringInflector::pluralize($this->getConfig()->entity);

        //Fallback to default
        if(!$this->getObject('manager')->getClass($identifier, false)) {
            $identifier = 'com://site/pages.model.entity.items';
        }

        if(!empty($data) && !is_numeric(key($data))) {
            $data = array($data);
        }

        $options = array('data' => $data);

        if($identity_key = $context->getIdentityKey()) {
            $options['identity_key'] = $identity_key;
        }

        return $this->getObject($identifier, $options);
    }

    protected function _actionCount(KModelContext $context)
    {
        if(!$context->state->isUnique()) {
            $result = count($context->data);
        } else {
            $result = 1;
        }

        return $result;
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }

    protected function _actionPersist(KModelContext $context)
    {
        return false;
    }

    public function getContext()
    {
        $context = new ComPagesModelContextCollection();
        $context->setSubject($this);
        $context->setState($this->getState());
        $context->setIdentityKey($this->getIdentityKey());

        return $context;
    }
}