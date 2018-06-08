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
        $path = $this->getModel()->fetch()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return parent::canRead();
    }

    public function canBrowse()
    {
        $path = $this->getModel()->getState()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return parent::canRead();
    }

    public function canAccess($path)
    {
        $registry = $this->getObject('page.registry');

        if($result = $registry->isPublished($path)) {
            $result =  $registry->isAccessible($path);
        }

        return $result;
    }
}