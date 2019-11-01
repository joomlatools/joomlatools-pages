<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPermissionCollection extends ComPagesControllerPermissionPage
{
    public function canAdd()
    {
        if(!$this->getPage()->isEditable()) {
            return false;
        }

        $path = $this->getPage()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return true;
    }


    public function canEdit()
    {
        if(!$this->getPage()->isEditable()) {
            return false;
        }

        $path = $this->getPage()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return true;
    }

    public function canDelete()
    {
        if(!$this->getPage()->isEditable()) {
            return false;
        }

        $path = $this->getPage()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return true;
    }
}