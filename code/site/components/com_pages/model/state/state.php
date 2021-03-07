<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelState extends KModelState
{
    public function insertUnique($name, $filter, $default = null)
    {
        return parent::insert($name, $filter, $default, true);
    }

    public function insertComposite($name, $filter, $related, $default = null)
    {
        return parent::insert($name, $filter, $default, true, $related, false);
    }

    public function insertRequired($name, $filter, $default = null)
    {
        return parent::insert($name, $filter, $default, false, true);
    }

    public function insertInternal($name, $filter, $default = null)
    {
        return parent::insert($name, $filter, $default, false, array(), true);
    }
}