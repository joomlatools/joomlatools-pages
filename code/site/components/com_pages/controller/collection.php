<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerCollection extends ComPagesControllerPage
{
    public function setModel($model)
    {
        //Create the collection model
        $model = $this->getObject('com://site/pages.model.factory')
            ->createCollection($this->getPage()->path, $this->getRequest()->query->toArray());

        return parent::setModel($model);
    }
}