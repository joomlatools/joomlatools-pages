<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesCollectionBehaviorSearchable extends ComPagesCollectionBehaviorQueryable
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
            'columns' => '',
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

        if ($search = $state->search)
        {
            // Parse $state->search for possible column prefix
            if (preg_match('#^([a-z0-9\-_]+)\s*:\s*(.+)\s*$#i', $search, $matches))
            {
                if (in_array($matches[1], $this->_columns))
                {
                    $this->_columns = (array) $matches[1];
                    $state->search  = $matches[2];
                }
            }

            return parent::_beforeFetch($context);
        }
    }

    protected function _beforeCount(KModelContextInterface $context)
    {
        return $this->_beforeFetch($context);
    }

    protected function _queryArray(array $data, KModelStateInterface $state)
    {
        $columns = $this->_columns;
        $value   = $state->search;

        $data = array_filter($data, function($item) use ($columns, $value)
        {
            foreach($columns as $column)
            {
                if (isset($item[$column]) && stripos($item[$column], $value) !== FALSE) {
                    return true;
                }
            }
        });

        return $data;
    }

    protected function _queryDatabase(KDatabaseQuerySelect $query, KModelStateInterface $state)
    {
        $conditions = array();

        if($columns = array_intersect($this->_columns, array_keys($this->getTable()->getColumns())))
        {
            foreach ($columns as $column)
            {
                if (in_array($column, $columns))
                {
                    $column = $this->getTable()->mapColumns($column);
                    $conditions[] = 'tbl.' . $column . ' LIKE :search';
                }
            }

            if ($conditions)
            {
                $query->where('(' . implode(' OR ', $conditions) . ')')
                    ->bind(array('search' => '%' . $state->search . '%'));
            }
        }
        else $query = false;

        return $query;
    }
}