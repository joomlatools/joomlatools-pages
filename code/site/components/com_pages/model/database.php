<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelDatabase extends ComPagesModelCollection
{
    protected $_table;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_table = $config->table;

        // Set the states based on the table columns
        foreach ($this->getTable()->getColumns() as $key => $column)
        {
            $required = $this->getTable()->mapColumns($column->related, true);
            $this->getState()->insert($key, $column->filter, null, $column->unique, $required);
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'table'  => '',
        ));

        parent::_initialize($config);
    }

    public function getData($count = false)
    {
        $query = $this->getObject('lib:database.query.select')
            ->table(array('tbl' => $this->getTable()->getName()));

        if($count) {
            $query->columns('COUNT(*)');
        } else {
            $query->columns('tbl.*');
        }

        if ($states = $this->getState()->getValues())
        {
            $columns = array_intersect_key($states, $this->getTable()->getColumns());
            $columns = $this->getTable()->mapColumns($columns);

            foreach ($columns as $column => $value)
            {
                if (isset($value))
                {
                    $query->where('tbl.' . $column . ' ' . (is_array($value) ? 'IN' : '=') . ' :' . $column)
                        ->bind(array($column => $value));
                }
            }
        }

        return $query;
    }

    public function getTable()
    {
        if(!($this->_table instanceof KDatabaseTableInterface))
        {
            //Make sure we have a table identifier
            if(!($this->_table instanceof KObjectIdentifier))
            {
                if(is_string($this->_table) && strpos($this->_table, '.') === false )
                {
                    $identifier         = $this->getIdentifier()->toArray();
                    $identifier['path'] = array('database', 'table');
                    $identifier['name'] = KStringInflector::underscore($this->_table);

                    $identifier = $this->getIdentifier($identifier);
                }
                else  $identifier = $this->getIdentifier($this->_table);

                if($identifier->path[1] != 'table') {
                    throw new UnexpectedValueException('Identifier: '.$identifier.' is not a table identifier');
                }

                $this->_table = $identifier;
            }

            $this->_table = $this->getObject($this->_table);

            if(!$this->_table instanceof KDatabaseTableInterface)
            {
                throw new UnexpectedValueException(
                    'Table: '.get_class($this->_table).' does not implement KDatabaseTableInterface'
                );
            }
        }

        return $this->_table;
    }

    protected function _actionFetch(KModelContext $context)
    {
        $data = array();

        if($context->data instanceof KDatabaseQueryInterface)
        {
            $data = $this->getTable()
                ->select($context->data, KDatabase::FETCH_ARRAY_LIST, ['identity_column' => $this->_identity_key]);
        }

        return $this->create($data);
    }

    protected function _actionCount(KModelContext $context)
    {
        $result = 0;

        if($context->data instanceof KDatabaseQueryInterface) {
            $result = $this->getTable()->count($context->data);
        }

        return $result;
    }
}