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
            $pages = KObjectConfig::unbox($context->pages);
            $pages = array_filter( $pages, function($page) use ($context) {
                return $this->_accept($page, $context);
            });

            $context->entity = $pages;
            $context->pages  = $pages;
        }
    }

    protected function _canFilter($context)
    {
        return true;
    }

    abstract protected function _accept($page, $context);
}