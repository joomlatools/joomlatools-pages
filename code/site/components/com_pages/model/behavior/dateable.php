<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorDateable extends ComPagesModelBehaviorFilterable
{
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('year', 'int')
            ->insert('month', 'int')
            ->insert('day', 'int');
    }

    protected function _canFilter($context)
    {
        return (bool) ($context->state->year || $context->state->month || $context->state->day);
    }

    protected function _accept($entity, $context)
    {
        $result = true;

        if(isset($entity['date']))
        {
            //Get the timestamp
            if(!is_integer($entity['date'])) {
                $entity_date = strtotime($entity['date']);
            } else {
                $entity_date = $entity['date'];
            }

            if($context->state->year) {
                $result = ($context->state->year == date('Y', $entity_date));
            }

            if($result && $context->state->month) {
                $result = ($context->state->month == date('m', $entity_date));
            }

            if($result && $context->state->day) {
                $result = ($context->state->day == date('d', $entity_date));
            }
        }

        return $result;
    }
}