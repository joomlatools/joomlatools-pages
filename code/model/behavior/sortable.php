<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorSortable extends ComPagesModelBehaviorQueryable
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
            ->insert('sort', 'cmd', 'order')
            ->insert('order', 'word', 'asc');
    }

    /**
     * Split the sort state if format is [property,ASC|DESC]
     *
     * @param   KModelContextInterface $context A model context object
     * @return  void
     */
    protected function _afterReset(KModelContextInterface $context)
    {
        if($context->modified->contains('sort'))
        {
            if(strpos($context->state->sort, ',') !== false)
            {
                $context->state->sort = explode(',', $context->state->sort);
                foreach($context->state->sort as $key => $value)
                {
                    if(strtoupper($value) == 'DESC' || strtoupper($value) == 'ASC')
                    {
                        unset($context->state->sort[$key]);
                        $context->state->order = strtolower($value);
                    }
                }
            }

            //Support for JSOAPI spec: https://jsonapi.org/format/#fetching-sorting
            if($context->state->sort[0] == '-')
            {
                $context->state->sort = ltrim($context->state->sort, '-');
                $context->state->order = 'desc';
            }
        }
    }

    protected function _beforeCreate(KModelContextInterface $context)
    {
        if(!$context->state->isUnique())
        {
            $entity = $context->entity;

            //Add ordering property
            $ordering = 0;
            foreach($entity as $key => $item)
            {
                if(is_array($entity[$key])) {
                    $entity[$key]['ordering'] = ++$ordering;
                }
            }

            $context->entity = $entity;
        }
    }

    protected function _queryArray(array $data, KModelStateInterface $state)
    {
        if($state->sort && $state->sort != 'order')
        {
            usort($data, function($first, $second) use($state)
            {
                $sorting = 0;
                $name    = $state->sort;

                $first_value  = $first[$name];
                $second_value = $second[$name];

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
                    $data = array_reverse($data);
                    break;

                case 'random':
                case 'shuffle':
                    shuffle($data);
                    break;

            }
        }

        return $data;
    }

    protected function _queryDatabase(KDatabaseQuerySelect $query, KModelStateInterface $state)
    {
        if(!$query->isCountQuery())
        {
            if($state->sort && $state->sort != 'order')
            {
                $order = strtoupper($state->order);

                if(isset($query->columns[$state->sort])) {
                    $column = $query->columns[$state->sort];
                } else {
                    $column = 'tbl.'.$this->getTable()->mapColumns($state->sort);
                }

                $query->order($column, $order);
            }

            if($state->order && in_array($state->order, ['shuffle', 'random'])) {
                $query->shuffle();
            }

        }

        return $query;
    }
}