<?php
/**
 * Joomlatools Framework Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelBehaviorSortable extends KModelBehaviorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority'   => self::PRIORITY_HIGH,
        ));

        parent::_initialize($config);
    }

    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('sort', 'cmd')
            ->insert('direction', 'word', 'asc');
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if(!$context->state->isUnique())
        {
            $pages = KObjectConfig::unbox($context->pages);

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

            if($state->direction == 'desc') {
                $pages = array_reverse($pages);
            }

            $context->pages = $pages;
        }
    }
}