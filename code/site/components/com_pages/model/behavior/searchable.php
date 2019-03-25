<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorSearchable extends KModelBehaviorAbstract
{
    protected $_columns;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_columns = (array) KObjectConfig::unbox($config->columns);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'columns' => 'title',
        ));

        parent::_initialize($config);
    }

    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('search', 'string');
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if (!$state->isUnique())
        {
            if ($search = $state->search)
            {
                // Parse $state->search for possible column prefix
                if (preg_match('#^([a-z0-9\-_]+)\s*:\s*(.+)\s*$#i', $search, $matches))
                {
                    if (in_array($matches[1], $this->_columns))
                    {
                        $this->_columns = (array) $matches[1];
                        $search         = $matches[2];
                    }
                }

                if($context instanceof ComPagesModelContextDatabase && $context->query)
                {
                    $conditions = array();

                    if($columns = array_intersect($this->_columns, array_keys($context->subject->getTable()->getColumns())))
                    {
                        foreach ($columns as $column)
                        {
                            if (in_array($column, $columns))
                            {
                                $column = $context->subject->getTable()->mapColumns($column);
                                $conditions[] = 'tbl.' . $column . ' LIKE :search';
                            }
                        }

                        if ($conditions)
                        {
                            $context->query->where('(' . implode(' OR ', $conditions) . ')')
                                ->bind(array('search' => '%' . $search . '%'));
                        }
                    }
                    else $context->query = false;
                }

                if($context instanceof ComPagesModelContextCollection && $context->data)
                {
                    $columns = $this->_columns;
                    $value   = $search;

                    $context->data = array_filter($context->data, function($item) use ($columns, $value)
                    {
                        foreach($columns as $column)
                        {
                            if (isset($item[$column]) && stripos($item[$column], $value) !== FALSE) {
                                return true;
                            }
                        }
                    });
                }
            }
        }
    }

    protected function _beforeCount(KModelContextInterface $context)
    {
        return $this->_beforeFetch($context);
    }
}