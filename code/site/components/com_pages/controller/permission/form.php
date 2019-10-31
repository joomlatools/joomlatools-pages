<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPermissionForm extends ComPagesControllerPermissionPage
{
    public function canSubmit()
    {
        if(!$this->getModel()->getPage()->isForm()) {
            return false;
        }

        $path = $this->getModel()->fetch()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return true;
    }

    public function canAdd()
    {
        if(!$this->getModel()->getPage()->isForm()) {
            return false;
        }

        $path = $this->getModel()->fetch()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return true;
    }


    public function canEdit()
    {
        if(!$this->getModel()->getPage()->isForm()) {
            return false;
        }

        $path = $this->getModel()->fetch()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return true;
    }

    public function canDelete()
    {
        if(!$this->getModel()->getPage()->isForm()) {
            return false;
        }

        $path = $this->getModel()->fetch()->path;

        if(!$this->canAccess($path)) {
            return false;
        }

        return true;
    }
}