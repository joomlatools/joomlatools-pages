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
                if($matches = preg_split('#(and|or)#', $filters, null, PREG_SPLIT_DELIM_CAPTURE))
                {
                    array_unshift($matches, 'and');
                    $matches = array_chunk($matches, 2);

                    foreach($matches as $match)
                    {
                        $combination = strtoupper($match[0]);
                        $expression  = $match[1];

                        if($filter = preg_split('#^(\w+)\s*([eq|neq|gt|gte|lt|lte|in|nin]+)\s*(.+)\s*$#i', trim($expression), null, PREG_SPLIT_DELIM_CAPTURE))
                        {
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
                }
            }
            else
            {
                foreach ($filters as $attribute => $values)
                {
                    //Support multiple constraints on the same attribute
                    foreach((array) $values as $key => $value)
                    {
                        // Parse filter value for possible operator
                        if (preg_match('#^([eq|neq|gt|gte|lt|lte|in|nin]+):(.+)\s*$#i', $value, $matches))
                        {
                            $this->_filters[] = [
                                'attribute' => $attribute,
                                'key'       => !is_numeric($key) ? $key : null,
                                'operation' => $matches[1],
                                'values' => array_unique(explode(',', $matches[2])),
                                'combination' => 'AND'
                            ];
                        }
                        else
                        {
                            $this->_filters[] = [
                                'attribute' => $attribute,
                                'key'       => !is_numeric($key) ? $key : null,
                                'operation' => 'eq',
                                'values' => array_unique(explode(',', $value)),
                                'combination' => 'AND'
                            ];
                        }
                    }
                }
            }

            if($this->_filters) {
                return parent::_beforeFetch($context);
            }
        }
    }

    protected function _queryArray(array $data, KModelStateInterface $state)
    {
        $result = array();
        $items  = $data;

        foreach($this->_filters as $filter)
        {
            $filtered = array_filter($items, function ($item) use ($filter)
            {
                $attribute = $filter['attribute'];

                if(isset($item[$attribute]))
                {
                    $item_value = $item[$attribute];

                    //Handle one dimensional assiociate array values
                    if($key = $filter['key'])
                    {
                        if(isset($item_value[$key])) {
                            $item_value = $item_value[$key];
                        } else {
                            $item_value = null;
                        }
                    }
                }
                else $item_value = null;

                foreach ((array)$filter['values'] as $value)
                {
                    if(strtotime($value) && strtotime($item_value))
                    {
                        $value      = strtotime($value);
                        $item_value = strtotime($item_value);
                    }

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
                            if (is_null($item_value)) {
                                return true;
                            }
                        }
                        else
                        {
                            if ($item_value == $value) {
                                return true;
                            }
                        }
                    } //Not Equal
                    elseif ($filter['operation'] == 'neq')
                    {
                        if (strtolower($value) == "null" || is_null($value))
                        {
                            if (!is_null($item_value)) {
                                return true;
                            }
                        }
                        else
                        {
                            if($item_value != $value) {
                                return true;
                            }
                        }
                    }
                    //Greater Than
                    elseif ($filter['operation'] == 'gt' && $item_value > $value) {
                        return true;
                    }
                    //Greater Or Equal To
                    elseif ($filter['operation'] == 'gte' && $item_value >= $value) {
                        return true;
                    }
                    //Less Then
                    elseif ($filter['operation'] == 'lt' && $item_value < $value) {
                        return true;
                    }
                    //Less Or Equal To
                    elseif ($filter['operation'] == 'lte' && $item_value <= $value) {
                        return true;
                    }
                    //In
                    elseif ($filter['operation'] == 'in')
                    {
                        if(is_array($item_value) && in_array($value, $item_value)) {
                            return true;
                        }
                    }
                    //Not In
                    elseif ($filter['operation'] == 'nin')
                    {
                        if(!is_array($item_value) || !in_array($value, $item_value)) {
                            return true;
                        };
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
                    //In
                    elseif ($filter['operation'] == 'in') {
                        continue; //not supported for now
                    }
                    //Not In
                    elseif ($filter['operation'] == 'nin') {
                        continue; //not supported for now
                    }

                    //Convert to date if value is
                    if(strtotime($value)) {
                        $value = gmdate('Y-m-d H:i:s', strtotime($value));
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