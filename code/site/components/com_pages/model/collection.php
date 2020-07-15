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
    private $__type;
    private $__name;
    private $__persistable;

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
        $this->addCommandCallback('before.persist', '_beforePersist');

        //Set the type
        $this->__type = $config->type;

        //Set the name
        $this->__name = $config->name;

        //Set if the collection is persistable
        $this->__persistable = $config->persistable;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'entity' => $this->getIdentifier()->getName(),
            'type'             => '', //the collection type used when generating JSDNAPI
            'name'             => '', //the collection name used to generate this model
            'identity_key'     => null,
            'behaviors'   => [
                'com://site/pages.model.behavior.paginatable',
                'com://site/pages.model.behavior.sortable',
                'com://site/pages.model.behavior.searchable',
                'com://site/pages.model.behavior.sparsable',
                'com://site/pages.model.behavior.filterable',
            ],
            'persistable' => false,
        ]);

        parent::_initialize($config);
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

    public function getName()
    {
        return $this->__name;
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

    public function getHash()
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

    public function filterItem(&$item, KModelStateInterface $state)
    {
        foreach($state->getValues(true) as $key => $value)
        {
            if(isset($item[$key]) && !in_array($item[$key], (array) $value)) {
                return false;
            }
        }

        return true;
    }

    protected function _prepareContext(KModelContext $context)
    {
        //Fetch the data
        $data = $this->fetchData($context->getName() == 'before.count');

        //Filter the data
        $context->data = $this->filterData($data);
    }

    protected function _actionFetch(KModelContext $context)
    {
        $entities = $this->create($context->data);

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
        $options = array();

        //Set the entity identifier
        $identifier = $this->getConfig()->entity;
        if(is_string($identifier) && strpos($identifier, '.') === false )
        {
            $identifier = $this->getIdentifier()->toArray();
            $identifier['path'] = ['model', 'entity'];
            $identifier['name'] = KStringInflector::pluralize($this->getConfig()->entity);
        }

        $options['entity'] = $this->getIdentifier($identifier);

        //Set the identitiy key
        if($identity_key = $context->getIdentityKey()) {
            $options['identity_key'] = $identity_key;
        }

        //Delegate entity instantiation
        $entity = $this->getObject('com://site/pages.model.entity.items', $options);

        // Insert the data
        $data = $context->entity;
        if(!empty($data) && !is_numeric(key($data))) {
            $data = array($data);
        }

        foreach($data as $properties) {
            $entity->create($properties);
        }

        return $entity;
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

    protected function _beforePersist(KModelContext $context)
    {
        return $this->isPersistable();
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

    public function isPersistable()
    {
        return $this->__persistable;
    }
}