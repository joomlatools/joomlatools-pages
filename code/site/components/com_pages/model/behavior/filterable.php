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
            if(!is_array($filters))
            {
                $matches = preg_split('#(and|or|xor)#', $filters, null, PREG_SPLIT_DELIM_CAPTURE);

                array_unshift($matches, 'and');
                $matches = array_chunk($matches, 2);

                foreach($matches as $match)
                {
                    $combination = strtoupper($match[0]);
                    $expression  = $match[1];

                    $filter = preg_split('#^(\w+)\s*([eq|neq|gt|gte|lt|lte|]+)\s*(.+)\s*$#i', trim($expression), null, PREG_SPLIT_DELIM_CAPTURE);

                    $attribute = $filter[1];
                    $operation = $filter[2];
                    $values    = $filter[3];

                    $this->_filters[] = [
                        'attribute' => $attribute,
                        'operation' => $operation,
                        'values'    => array_unique(explode(',',  $values)),
                        'combination' => $combination
                    ];
                }
            }
            else
            {
                foreach ($filters as $attribute => $value)
                {
                    // Parse filter value for possible operator
                    if (preg_match('#^([eq|neq|gt|gte|lt|lte|]+):(.+)\s*$#i', $value, $matches))
                    {
                        $this->_filters[] = [
                            'attribute' => $attribute,
                            'operation' => $matches[1],
                            'values' => array_unique(explode(',', $matches[2])),
                            'combination' => 'AND'

                        ];
                    }
                    else
                    {
                        $this->_filters[] = [
                            'attribute' => $attribute,
                            'operation' => 'eq',
                            'values' => array_unique(explode(',', $value)),
                            'combination' => 'AND'
                        ];
                    }
                }
            }

            if($this->_filters) {
                return parent::_beforeFetch($context);
            }
        }
    }

    protected function _beforeCount(KModelContextInterface $context)
    {
        return $this->_beforeFetch($context);
    }

    protected function _queryArray(array $data, KModelStateInterface $state)
    {
        $result = array();
        $items  = $data;

        foreach($this->_filters as $filter)
        {
            $filtered = array_filter($items, function ($item) use ($filter)
            {
                foreach ((array)$filter['values'] as $value)
                {
                    $attribute = $filter['attribute'];

                    //Convert boolean strings
                    if(strtolower($value) == 'false') {
                        $value = false;
                    }

                    if(strtolower($value) == 'true') {
                        $value = true;
                    }

                    //Equal
                    if ($filter['operation'] == 'eq')
                    {
                        if (strtolower($value) == "null" || is_null($value))
                        {
                            if (is_null($item[$attribute])) {
                                return true;
                            }
                        }
                        else
                        {
                            if ($item[$attribute] == $value) {
                                return true;
                            }
                        }
                    } //Not Equal
                    elseif ($filter['operation'] == 'neq')
                    {
                        if (strtolower($value) == "null" || is_null($value))
                        {
                            if (!is_null($item[$attribute])) {
                                return true;
                            }
                        }
                        else
                        {
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
            });

            if($filter['combination'] == 'AND')
            {
                $items  = $filtered;
                $result = $filtered;
            }

            if($filter['combination'] == 'OR')
            {
                $items  = $data;
                $result = $result + $filtered;
            }

        }

        return $result;
    }

    protected function _queryDatabase(KDatabaseQuerySelect $query, KModelStateInterface $state)
    {
        $table = $this->getTable();
        foreach ($this->_filters as $filter)
        {
            if (isset($filter['values']))
            {
                $combination = $filter['combination'];
                $column      = $table->mapColumns($filter['attribute']);

                foreach ((array)$filter['values'] as $key => $value)
                {
                    $parameter = $column . $key;

                    //Equal
                    if ($filter['operation'] == 'eq')
                    {
                        if (strtolower($value) == "null" || is_null($value)) {
                            $expression = 'tbl.' . $column . ' IS NULL';
                        } else {
                            $expression = 'tbl.' . $column . ' = :' . $parameter;
                        }
                    } //Not Equal
                    elseif ($filter['operation'] == 'neq')
                    {
                        if (strtolower($value) == "null" || is_null($value)) {
                            $expression = 'tbl.' . $column . ' IS NOT NULL';
                        } else {
                            $expression = 'tbl.' . $column . ' != :' . $parameter;
                        }
                    } //Greater Than
                    elseif ($filter['operation'] == 'gt') {
                        $expression = 'tbl.' . $column . ' > :' . $parameter;
                    } //Greater Or Equal To
                    elseif ($filter['operation'] == 'gte') {
                        $expression = 'tbl.' . $column . ' >= :' . $parameter;
                    } //Less Then
                    elseif ($filter['operation'] == 'lt') {
                        $expression = 'tbl.' . $column . ' < :' . $parameter;
                    } //Less Or Equal To
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