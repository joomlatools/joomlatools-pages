<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesModelBehaviorFilterable extends KModelBehaviorAbstract
{
    protected function _beforeCount(KModelContextInterface $context)
    {
        $this->_beforeFetch($context);
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        //Only filter collections
        if(!$context->state->isUnique() && $this->_canFilter($context))
        {
            $entities = KObjectConfig::unbox($context->entity);

            $context->entity = array_filter($entities, function($entity) use ($context) {
                return $this->_accept($entity, $context);
            });
        }
    }

    protected function _canFilter($context)
    {
        return true;
    }

    abstract protected function _accept($entity, $context);
}