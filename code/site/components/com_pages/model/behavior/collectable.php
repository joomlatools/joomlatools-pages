<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorCollectable extends ComPagesModelBehaviorFilterable
{
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('collection', 'boolean');
    }

    protected function _canFilter($context)
    {
        return !is_null($context->state->collection);
    }

    protected function _accept($page, $context)
    {
        if($context->state->collection === true) {
            $result = isset($page['collection']) && $page['collection'] !== false;
        }

        if($context->state->collection === false) {
            $result = !isset($page['collection']) || $page['collection'] === false;
        }

        return $result;
    }
}