<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorVisible extends ComPagesModelBehaviorFilterable
{
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('visible', 'boolean');
    }

    protected function _canFilter($context)
    {
        return !is_null($context->state->visible);
    }

    protected function _accept($page, $context)
    {
        if($context->state->visible === true) {
            $result = !isset($page['visible']) || $page['visible'] !== false;
        }

        if($context->state->visible === false) {
            $result = isset($page['visible']) && $page['visible'] === false;
        }

        return $result;
    }
}