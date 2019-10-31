<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityRows extends ComPagesModelEntityItems implements KDatabaseRowsetInterface
{
    private $__table;

    public function setTable($table)
    {
        $this->__table = $table;

        foreach ($this as $entity) {
            $entity->setTable($table);
        }
    }

    public function getTable()
    {
        return $this->__table;
    }

    public function getIdentityColumn()
    {
        return $this->getIdentityKey();
    }
}