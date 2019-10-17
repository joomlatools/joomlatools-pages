<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesModelCollection extends KModelAbstract implements ComPagesModelInterface
{
    private $__data;
    private $__type;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Insert the identity_key
        if($config->identity_key) {
            $this->getState()->insert($config->identity_key, 'cmd', null, true);
        }

        //Setup callbacks
        $this->addCommandCallback('before.fetch', '_prepareContext');
        $this->addCommandCallback('before.count', '_prepareContext');

        //Set the type
        $this->__type = $config->type;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'type' =>  KStringInflector::pluralize($this->getIdentifier()->getName()),
            'behaviors'   => [
                'com://site/pages.model.behavior.paginatable',
                'com://site/pages.model.behavior.sortable',
                'com://site/pages.model.behavior.searchable',
                'com://site/pages.model.behavior.sparsable'
            ],
            'state' => 'com://site/pages.model.state.collection',
        ]);

        parent::_initialize($config);
    }

    public function getType()
    {
        return $this->__type;
    }

    public function getData($count = false)
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
        if($state->isUnique())
        {
            foreach($state->getValues(true) as $key => $value)
            {
                if(!in_array($item[$key], (array) $value)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function _prepareContext(KModelContext $context)
    {
        if(!$this->__data) {
            $this->__data = $this->getData($context->getName() == 'before.count');
        }

        $context->data = $this->__data;
    }

    protected function _actionFetch(KModelContext $context)
    {
        $data = $this->filterData($context->data);
        $entities = $this->create($data);


        return $entities;
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

    protected function _actionCreate(KModelContext $context)
    {
        $data = KModelContext::unbox($context->entity);

        $identifier = $this->getIdentifier()->toArray();
        $identifier['path'] = ['model', 'entity'];
        $identifier['name'] = KStringInflector::pluralize($identifier['name']);

        //Fallback to default
        if(!$this->getObject('manager')->getClass($identifier, false)) {
            $identifier = 'com://site/pages.model.entity.items';
        }

        $options = array('data' => $data);

        if($identity_key = $context->getIdentityKey()) {
            $options['identity_key'] = $identity_key;
        }

        return $this->getObject($identifier, $options);
    }

    public function getContext()
    {
        $context = new ComPagesModelContextCollection();
        $context->setSubject($this);
        $context->setState($this->getState());
        $context->setIdentityKey($this->_identity_key);

        return $context;
    }
}