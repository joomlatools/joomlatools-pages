<?php
/**
 * Joomlatools Framework Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelBehaviorAccessible extends KModelBehaviorAbstract
{
    protected function _beforeCount(KModelContextInterface $context)
    {
        $this->_beforeFetch($context);
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if(!$context->state->isUnique())
        {
            $pages    = KObjectConfig::unbox($context->pages);
            $registry = $this->getObject('page.registry');

            $pages = array_filter($pages, function($value) use ($registry) {
                return $registry->isPublished($value['path']) && $registry->isAccessible($value['path']);
            });

            $context->pages = $pages;
        }
    }
}