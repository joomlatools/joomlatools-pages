<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelCollection extends ComPagesModelAbstract
{
    protected $_data;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Insert the identity_key
        if($config->identity_key) {
            $this->getState()->insert($config->identity_key, 'cmd', null, true);
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors'   => [
                'com:pages.model.behavior.paginatable',
                'com:pages.model.behavior.sortable',
                'com:pages.model.behavior.searchable'
            ],
            'identity_key' => 'id',
        ]);

        parent::_initialize($config);
    }

    public function getData()
    {
        return (array) $this->_data;
    }

    public function filterData($item, KModelStateInterface $state)
    {
        return true;
    }

    protected function _prepareContext(KModelContext $context)
    {
        $data = (array) $this->getData();

        //Only filter collections
        if(!$context->state->isUnique())
        {
            $context->data = array_filter($data, function($item) use ($context) {
                return $this->filterData($item, $context->state);
            });
        }
        else $context->data = $data;
    }

    protected function _actionFetch(KModelContext $context)
    {
        $entities = $this->create($context->data);

        //Find the unique entity
        if($context->state->isUnique()) {
            $entities = $entities->find($context->state->getValues(true));
        }

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
        $this->_data = null;

        parent::_actionReset($context);
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