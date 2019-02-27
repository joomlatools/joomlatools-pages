<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesModelAbstract extends KModelAbstract
{
    protected $_data;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('before.fetch', '_prepareContext');
        $this->addCommandCallback('before.count', '_prepareContext');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors'   => ['com:pages.model.behavior.paginatable'],
            'identity_key' => 'id',
        ]);

        parent::_initialize($config);
    }

    public function getData()
    {
        return array();
    }

    protected function _prepareContext(KModelContext $context)
    {
        $context->entity = (array) $this->getData();
    }

    protected function _actionFetch(KModelContext $context)
    {
        return $this->_actionCreate($context);
    }

    protected function _actionCount(KModelContext $context)
    {
        return count($this->getData());
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

    protected function _actionReset(KModelContext $context)
    {
        $this->_data = null;

        parent::_actionReset($context);
    }
}