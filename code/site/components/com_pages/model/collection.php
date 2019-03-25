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
    protected $_data;

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

    public function getData($count = false)
    {
        return (array) $this->_data;
    }

    protected function _prepareContext(KModelContext $context)
    {
        $context->data = $this->getData($context->getName() == 'before.count');
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

    protected function _actionCreate(KModelContext $context)
    {
        $data = KModelContext::unbox($context->entity);

        $identifier = $this->getIdentifier()->toArray();
        $identifier['path'] = ['model', 'entity'];
        $identifier['name'] = KStringInflector::pluralize($identifier['name']);

        //Fallback to default
        if(!$this->getObject('manager')->getClass($identifier, false)) {
            $identifier = 'com:pages.model.entity.items';
        }

        $options = array(
            'data'         => $data,
            'identity_key' => $context->getIdentityKey()
        );

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