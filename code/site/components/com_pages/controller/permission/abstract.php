<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPermissionAbstract extends ComKoowaControllerPermissionAbstract
{
    public function canRead()
    {
        $path = $this->getPage()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return parent::canRead();
    }

    public function canBrowse()
    {
        $path = $this->getPage()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return parent::canRead();
    }

    public function canAccess($path)
    {
        $result = true;

        if($path)
        {
            $page = $this->getObject('page.registry')->getPage($path);

            //Check groups
            if(isset($page->access->groups))
            {
                $groups = $this->getObject('com://site/pages.database.table.groups')
                    ->select($this->getObject('user')->getGroups(), KDatabase::FETCH_ARRAY_LIST);

                $groups = array_map('strtolower', array_column($groups, 'title'));

                if(!array_intersect($groups, KObjectConfig::unbox($page->access->groups))) {
                    $result = false;
                }
            }

            //Check roles
            if($result && isset($page->access->roles))
            {
                $roles = $this->getObject('com://site/pages.database.table.roles')
                    ->select($this->getObject('user')->getRoles(), KDatabase::FETCH_ARRAY_LIST);

                $roles = array_map('strtolower', array_column($roles, 'title'));

                if(!array_intersect($roles, KObjectConfig::unbox($page->access->roles))) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}