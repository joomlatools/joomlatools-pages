<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPermissionPage extends ComKoowaControllerPermissionAbstract
{
    public function canRead()
    {
        if(!$this->canAccess()) {
            return false;
        }

        return parent::canRead();
    }

    public function canAccess()
    {
        $result = true;

        $page = $this->getModel()->fetch();

        //Only editors can access unpublished pages
        if(!$this->canEdit() && !$page->published) {
           $result = false;
        }

        //Check user group access
        if($result)
        {
            $groups = $this->getObject('com:users.database.table.groups')
                ->select($this->getUser()->getGroups(), KDatabase::FETCH_ARRAY_LIST);

            $groups = array_map('strtolower', array_column($groups, 'title'));

            if(!array_intersect($groups, $page->access->groups->toArray())) {
                $result = false;
            }
        }

        //Check user roles access
        if($result)
        {
            $roles = $this->getObject('com:users.database.table.roles')
                ->select($this->getUser()->getRoles(), KDatabase::FETCH_ARRAY_LIST);

            $roles = array_map('strtolower', array_column($roles, 'title'));

            if(!array_intersect($roles, $page->access->roles->toArray())) {
                $result = false;
            }
        }

        return $result;
    }
}