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
        return (bool) $context->state->visible;
    }

    protected function _accept($page, $context)
    {
        return (isset($page['visible']) && $page['visible'] == false) ? false : true;
    }
}