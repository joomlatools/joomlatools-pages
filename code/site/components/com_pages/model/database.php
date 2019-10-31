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
    private $__table;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__table = $config->table;

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
            'entity'       => 'row',
            'table'        => '',
        ));

        parent::_initialize($config);
    }

    public function fetchData($count = false)
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

    public function getIdentityKey()
    {
        return $this->getTable()->getIdentityColumn();
    }

    public function getTable()
    {
        if(!($this->__table instanceof KDatabaseTableInterface))
        {
            //Make sure we have a table identifier
            if(!($this->__table instanceof KObjectIdentifier))
            {
                if(is_string($this->__table) && strpos($this->__table, '.') !== false ) {
                    $this->__table = $this->getObject($this->__table);
                } else {
                    $this->__table = $this->getObject('com://site/pages.database.table.default', array('name' => $this->__table));
                }
            }

            if(!$this->__table instanceof KDatabaseTableInterface)
            {
                throw new UnexpectedValueException(
                    'Table: '.get_class($this->__table).' does not implement KDatabaseTableInterface'
                );
            }
        }

        return $this->__table;
    }

    protected function _actionFetch(KModelContext $context)
    {
        $data = array();

        if($context->data instanceof KDatabaseQueryInterface)
        {
            $data = $this->getTable()
                ->select($context->data, KDatabase::FETCH_ARRAY_LIST, ['identity_column' => $this->getIdentityKey()]);
        }

        $entities = $this->create($data);

        //Mark the entities as fetched
        foreach($entities as $key => $entity) {
            $entity->setStatus(ComPagesModelEntityItem::STATUS_FETCHED);
        }

        return $entities;
    }

    protected function _actionCount(KModelContext $context)
    {
        $result = 0;

        if($context->data instanceof KDatabaseQueryInterface) {
            $result = $this->getTable()->count($context->data);
        }

        return $result;
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result = true;

        foreach($context->entity as $entity)
        {
            if($entity->getStatus() == $entity::STATUS_CREATED) {
                $result = $this->getTable()->insert($entity);
            }

            if($entity->getStatus() == $entity::STATUS_UPDATED) {
                $result = $this->getTable()->update($entity);
            }

            if($entity->getStatus() == $entity::STATUS_DELETED) {
                $result = $this->getTable()->delete($entity);
            }

            //Reset the modified array
            if (((integer) $result) > 0) {
                $entity->resetModified();
            }

            if($result === false) {
                break;
            }
        }

        return $result;
    }
}