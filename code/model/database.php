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
    private $__data;

    protected $_hash_key;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__table = $config->table;

        // Set the dynamic states based on the unique table keys
        foreach ($this->getTable()->getUniqueColumns() as $key => $column)
        {
            if(!empty($column->related))
            {
                $related = $this->getTable()->mapColumns($column->related, true);
                $this->getState()->insertComposite($key, $column->filter, $related);
            }
            else $this->getState()->insertUnique($key, $column->filter);
        }

        $this->_hash_key  = $config->hash_key;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'persistable'  => true,
            'table'        => '',
            'hash_key'     => '',
            'search'       => [], //properties to allow searching on
        ))->append([
            'behaviors'   => [
                'com:pages.model.behavior.paginatable',
                'com:pages.model.behavior.sortable',
                'com:pages.model.behavior.sparsable',
                'com:pages.model.behavior.filterable',
                'com:pages.model.behavior.searchable' => ['columns' => $config->search],
            ],
        ]);

        parent::_initialize($config);
    }

    protected function _initializeContext(KModelContext $context)
    {
        //Validate the state
        $this->_validateState($context->state);

        if($context->action == 'count' || $context->action == 'hash') {
            $query = $this->getQuery(false);
        }  else {
            $query = $this->getQuery();
        }

        if($context->action == 'count') {
            $query->columns('COUNT(*)');
        }

        $context->data = $query;
    }

    public function fetchData()
    {
        return $this->__data;
    }

    public function getTable()
    {
        if(!($this->__table instanceof KDatabaseTableInterface))
        {
            //Make sure we have a table identifier
            if(!($this->__table instanceof KObjectIdentifier))
            {
                if(is_string($this->__table) && strpos($this->__table, '.') === false )
                {
                    $identifier = 'com:pages.database.table.'.$this->__table;
                    $this->__table = $this->getObject($identifier, array('name' => $this->__table));
                }
                else $this->__table = $this->getObject($this->__table);
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

    protected function getQuery($columns = true)
    {
        $query = $this->getObject('lib:database.query.select')
            ->table(array('tbl' => $this->getTable()->getName()));

        if($columns) {
            $query->columns('tbl.*');
        }

        if ($states = $this->getState()->getValues())
        {
            $columns = $this->getTable()->filter($states);

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
        if(!$key = $this->getConfig()->identity_key)
        {
            if(!$this->getTable()->getIdentityColumn()) {
                $key = parent::getIdentityKey();
            } else {
                $key = 'id';
            }
        }

         return $key;
    }

    public function _actionHash(KModelContext $context)
    {
        $hash = parent::_actionHash($context);

        if($this->_hash_key)
        {
            $query = $context->data;

            $id  = $this->getIdentityKey();
            $key = $this->getTable()->mapColumns($this->_hash_key);

            $query->columns(['hash' => 'CRC32(GROUP_CONCAT(DISTINCT tbl.'.$id.', "-", tbl.'.$key.'))']);

            $hash = $this->getTable()->select($query, KDatabase::FETCH_FIELD);
        }
        else
        {
            if($modified = $this->getTable()->getSchema()->modified) {
                $hash = hash('crc32b', $modified);
            }
        }

        return $hash;
    }

    protected function _actionFetch(KModelContext $context)
    {
        $data = array();

        if($context->data instanceof KDatabaseQueryInterface)
        {
            $data = $this->getTable()->select($context->data, KDatabase::FETCH_ARRAY_LIST);
            $data = array_values($data);

            //Store the raw data
            $this->__data = $data;
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
            //Cast the entity to KDatabaseRowInterface
            if(!$entity instanceof KDatabaseRowInterface)
            {
                $row = $this->getTable()->createRow([
                    'data' => $entity->getProperties()
                ]);
            }

            try
            {
                if($entity->getStatus() == $entity::STATUS_CREATED) {
                    $result = $this->getTable()->insert($row);
                }

                if($entity->getStatus() == $entity::STATUS_UPDATED) {
                    $result = $this->getTable()->update($row);
                }

                if($entity->getStatus() == $entity::STATUS_DELETED) {
                    $result = $this->getTable()->delete($row);
                }
            }
            catch(RuntimeException $exception)
            {
                if($exception->getCode() == 1062) {
                    throw new ComPagesModelExceptionConflict($exception->getMessage());
                } else {
                    throw new ComPagesModelExceptionError();
                }
            }

            if($result !== false)
            {
                if($result > 0)
                {
                    $result = self::PERSIST_SUCCESS;
                    $entity->resetModified();
                }
                else $result = self::PERSIST_NOCHANGE;
            }
            else
            {
                $result = self::PERSIST_FAILURE;
                break;
            }
        }

        return $result;
    }
}