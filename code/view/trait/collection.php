<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

trait ComPagesViewTraitCollection
{
    public function getCollection($model = '', $state = [])
    {
        if($model) {
            $result = $this->getObject('model.factory')->createModel($model, $state)->fetch();
        } else {
            $result = $this->getModel()->fetch();
        }

        return $result;
    }

    public function getState()
    {
        return $this->getModel()->getState();
    }

    public function isCollection()
    {
        return (bool) !$this->getState()->isUnique();
    }
}
