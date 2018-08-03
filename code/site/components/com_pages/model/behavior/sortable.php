<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorSortable extends KModelBehaviorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'priority'   => self::PRIORITY_HIGH,
        ]);

        parent::_initialize($config);
    }

    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('sort', 'cmd', 'title')
            ->insert('order', 'word', 'asc');
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if(!$state->isUnique())
        {
            $pages = KObjectConfig::unbox($context->pages);

            if($state->sort)
            {
                usort($pages, function($first, $second) use($state)
                {
                    $sorting = 0;
                    $name    = $state->sort;

                    $first_value  = $first[$name];
                    $second_value = $second[$name];

                    if($name == 'date')
                    {
                        $first_value  = strtotime($first_value);
                        $second_value = strtotime($second_value);
                    }

                    if($first_value > $second_value) {
                        $sorting = 1;
                    } elseif ($first_value < $second_value) {
                        $sorting = -1;
                    }

                    return $sorting;
                });
            }

            if($state->order)
            {
                switch($state->order)
                {
                    case 'desc':
                    case 'descending':
                        $pages = array_reverse($pages);
                        break;

                    case 'shuffle':
                        shuffle($pages);
                        break;

                }
            }

            $context->pages  = $pages;
            $context->entity = $pages;
        }
    }
}