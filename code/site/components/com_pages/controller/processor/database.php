<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerProcessorDatabase extends ComPagesControllerProcessorAbstract
{
    protected $_table;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_table = $config->table;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'table' => '',
        ]);

        parent::_initialize($config);
    }


    public function getTable()
    {
        if(!($this->_table instanceof KDatabaseTableInterface))
        {
            //Make sure we have a table identifier
            if(!($this->_table instanceof KObjectIdentifier))
            {
                if(is_string($this->_table) && strpos($this->_table, '.') !== false ) {
                    $this->_table = $this->getObject($this->_table);
                } else {
                    $this->_table = $this->getObject('com://site/pages.database.table.default', array('name' => $this->_table));
                }
            }

            if(!$this->_table instanceof KDatabaseTableInterface)
            {
                throw new UnexpectedValueException(
                    'Table: '.get_class($this->_table).' does not implement KDatabaseTableInterface'
                );
            }
        }

        return $this->_table;
    }

    public function processData(array $data)
    {
        $table = $this->getTable();
        $table->insert($table->createRow(['data' => $data]));
    }
}