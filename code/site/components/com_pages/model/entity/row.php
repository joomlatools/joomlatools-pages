<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityRow extends ComPagesModelEntityItem implements KDatabaseRowInterface
{
    private $__table;

    public function setTable($table)
    {
        $this->__table = $table;
    }

    public function getTable()
    {
        return $this->__table;
    }

    public function getIdentityColumn()
    {
       return $this->getIdentityKey();
    }

    public function reset()
    {
        parent::reset();

        if($table = $this->getTable()) {
            $this->_data = $table->getDefaults();
        }

        return $this;
    }
}