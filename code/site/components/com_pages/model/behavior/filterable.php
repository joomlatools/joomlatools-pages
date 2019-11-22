<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorFilterable extends ComPagesModelBehaviorQueryable
{
    protected $_filters;

    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('filter', 'string');
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        if($filters = $context->state->filter)
        {
            foreach ($filters as $attribute => $value)
            {
                // Parse filter value for possible operator
                if (preg_match('#^([eq|neq|gt|gte|lt|lte|]+):(.+)\s*$#i', $value, $matches))
                {
                    $this->_filters[$attribute] = [
                        'operation' => $matches[1],
                        'values'    => array_unique(explode(',',  $matches[2]))
                    ];
                }
                else
                {
                    $this->_filters[$attribute] = [
                        'operation' => 'eq',
                        'values'    => array_unique(explode(',', $value))
                    ];
                }
            }

            if($this->_filters) {
                return parent::_beforeFetch($context);
            }
        }
    }

    protected function _queryArray(array $data, KModelStateInterface $state)
    {
        $filters = $this->_filters;

        foreach($filters as $attribute => $filter)
        {
            $data = array_filter($data, function ($item) use ($attribute, $filter)
            {
                if (array_key_exists($attribute, $item))
                {
                    foreach ((array)$filter['values'] as $value)
                    {
                        //Equal
                        if ($filter['operation'] == 'eq')
                        {
                            if (strtolower($value) == "null" || is_null($value)) {
                                if (is_null($item[$attribute])) {
                                    return true;
                                }
                            } else {
                                if ($item[$attribute] == $value) {
                                    return true;
                                }
                            }
                        } //Not Equal
                        elseif ($filter['operation'] == 'neq')
                        {
                            if (strtolower($value) == "null" || is_null($value)) {
                                if (!is_null($item[$attribute])) {
                                    return true;
                                }
                            } else {
                                if ($item[$attribute] != $value) {
                                    return true;
                                }
                            }
                        }
                        //Greater Than
                        elseif ($filter['operation'] == 'gt' && $item[$attribute] > $value) {
                            return true;
                        }
                        //Greater Or Equal To
                        elseif ($filter['operation'] == 'gte' && $item[$attribute] >= $value) {
                            return true;
                        }
                        //Less Then
                        elseif ($filter['operation'] == 'lt' && $item[$attribute] < $value) {
                            return true;
                        }
                        //Less Or Equal To
                        elseif ($filter['operation'] == 'lte' && $item[$attribute] <= $value) {
                            return true;
                        }
                    }
                }
            });
        }

        return $data;
    }

    protected function _queryDatabase(KDatabaseQuerySelect $query, KModelStateInterface $state)
    {
        $filters = $this->_filters;

        $table   = $this->getTable();
        $columns = array_intersect_key($filters, $table->getColumns());
        $columns = $table->mapColumns($columns);

        foreach ($columns as $column => $filter)
        {
            if (isset($filter['values']))
            {
                $combination = 'AND';

                foreach((array) $filter['values'] as $key => $value)
                {
                    $parameter = $column.$key;

                    //Equal
                    if ($filter['operation'] == 'eq')
                    {
                        if (strtolower($value) == "null" || is_null($value)) {
                            $expression = 'tbl.' . $column . ' IS NULL';
                        } else {
                            $expression = 'tbl.' . $column . ' = :' . $parameter;
                        }
                    }
                    //Not Equal
                    elseif ($filter['operation'] == 'neq')
                    {
                        if (strtolower($value) == "null" || is_null($value)) {
                            $expression = 'tbl.' . $column . ' IS NOT NULL';
                        } else {
                            $expression = 'tbl.' . $column . ' != :' . $parameter;
                        }
                    }
                    //Greater Than
                    elseif ($filter['operation'] == 'gt') {
                        $expression = 'tbl.' . $column . ' > :' . $parameter;
                    }
                    //Greater Or Equal To
                    elseif ($filter['operation'] == 'gte') {
                        $expression = 'tbl.' . $column . ' >= :' . $parameter;
                    }
                    //Less Then
                    elseif ($filter['operation'] == 'lt') {
                        $expression = 'tbl.' . $column . ' < :' . $parameter;
                    }
                    //Less Or Equal To
                    elseif ($filter['operation'] == 'lte') {
                        $expression = 'tbl.' . $column . ' <= :' . $parameter;
                    }

                    $query->where($expression, $combination)->bind(array($parameter => $value));

                    //Multiple values for the same filter are OR
                    $combination = 'OR';
                }
            }
        }

        return $query;
    }
}