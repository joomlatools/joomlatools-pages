<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelState extends KModelState
{
    public function isUnique()
    {
        $unique = false;

        //Locate the template
        if ($file = $this->getObject('template.locator.factory')->locate($this->path))
        {
            if(strpos(basename($file), 'index') === 0) {
               $unique = true;
           }
        }

        return $unique;
    }
}