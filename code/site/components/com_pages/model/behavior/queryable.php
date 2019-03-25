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
    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if(!$state->isUnique() && $context instanceof ComPagesModelContextCollection)
        {
            if($context->data) {
                $context->data = $this->_queryCollection($context->data, $state);
            }

            if($context->query) {
                $context->query = $this->_queryDatabase($context->query, $state);
            }
        }
    }

    protected function _queryCollection(array $data, KModelStateInterface $state)
    {
        return $data;
    }

    protected function _queryDatabase(KDatabaseQuerySelect $query, KModelStateInterface $state)
    {
        return $query;
    }
}