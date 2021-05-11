<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesDatabaseTableAbstract extends KDatabaseTableAbstract
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Reset identity column only re-adding it for an auto increment primary key
        $this->_identity_column = null;
        unset($this->_column_map['id']);

        foreach ($this->getColumns(true) as $column)
        {
            if ($column->autoinc)
            {
                $this->_identity_column  = $column->name;
                $this->_column_map['id'] = $column->name;
                break;
            }
        }
    }
}