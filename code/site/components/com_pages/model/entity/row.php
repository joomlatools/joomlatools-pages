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

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            //'internal_properties' => ['id'],
        ]);

        parent::_initialize($config);
    }

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
}