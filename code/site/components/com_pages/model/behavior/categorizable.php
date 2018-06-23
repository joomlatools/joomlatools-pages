<?php
/**
 * Joomlatools Framework Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelBehaviorCategorizable extends ComPagesModelBehaviorFilterable
{
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('category', 'cmd');
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if(!$state->isUnique() && $state->category)
        {
            $pages = KObjectConfig::unbox($context->pages);

            $pages = array_filter( $pages, function($page) use ($state) {
                return $page['category'] == $state->category;
            });

            $context->pages = $pages;
        }

        parent::_beforeFetch($context);
    }
}