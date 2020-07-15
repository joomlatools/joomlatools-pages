<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

class ComPagesModelContextCollection extends KModelContext
{
    private $__data;
    private $__entity;

    public function setData($data)
    {
        return $this->__data = $data;
    }

    public function getData()
    {
        return $this->__data;
    }

    public function setEntity($entity)
    {
        return $this->__entity = $entity;
    }

    public function getEntity()
    {
        return $this->__entity;
    }
}