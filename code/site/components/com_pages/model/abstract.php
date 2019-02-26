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

    public function getData()
    {
        return array();
    }

    protected function _prepareContext(KModelContext $context)
    {
        $context->entity = $this->getData();
    }

    protected function _actionFetch(KModelContext $context)
    {
        return parent::_actionCreate($context);
    }

    protected function _actionCount(KModelContext $context)
    {
        return count($this->getData());
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->_data = null;

        parent::_actionReset($context);
    }
}