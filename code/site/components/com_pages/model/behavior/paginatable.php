<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorPaginatable extends KModelBehaviorPaginatable
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'priority' => self::PRIORITY_LOW,
        ]);

        parent::_initialize($config);
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if(!$state->isUnique())
        {
            if ($limit = $state->limit)
            {
                $offset = $state->offset;
                $total  = $context->subject->count();

                if ($offset !== 0 && $total !== 0)
                {
                    // Recalculate the offset if it is set to the middle of a page.
                    if ($offset % $limit !== 0) {
                        $offset -= ($offset % $limit);
                    }

                    // Recalculate the offset if it is higher than the total
                    if ($offset >= $total) {
                        $offset = floor(($total - 1) / $limit) * $limit;
                    }

                    $state->offset = $offset;
                }

                if($context instanceof ComPagesModelContextCollection && $context->data) {
                    $context->data = array_slice($context->data, $offset, $limit);
                }

                if($context instanceof ComPagesModelContextDatabase && $context->query) {
                    $context->query->limit($limit, $offset);
                }
            }
        }
    }
}