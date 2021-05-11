<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesModelBehaviorQueryable extends KModelBehaviorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'filter' => true
        ));

        parent::_initialize($config);
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if(!$state->isUnique() && $context instanceof ComPagesModelContextCollection)
        {
            if(is_array($context->data)) {
                $context->data = $this->_queryArray($context->data, $state);
            }

            if($context->data instanceof KDatabaseQuerySelect) {
                $context->data = $this->_queryDatabase($context->data, $state);
            }
        }
    }

    protected function _beforeCount(KModelContextInterface $context)
    {
        if($this->getConfig()->filter) {
            $result = $this->_beforeFetch($context);
        } else {
            $result = $context->data;
        }

        return $result;
    }

    protected function _queryArray(array $data, KModelStateInterface $state)
    {
        return $data;
    }

    protected function _queryDatabase(KDatabaseQuerySelect $query, KModelStateInterface $state)
    {
        return $query;
    }
}