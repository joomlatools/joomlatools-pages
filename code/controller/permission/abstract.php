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

        if($path) {
            $result = $this->getObject('page.registry')->isPageAccessible($path);
        }

        return $result;
    }
}