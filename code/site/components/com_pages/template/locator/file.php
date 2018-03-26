<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesTemplateLocatorFile extends ComKoowaTemplateLocatorFile
{
    /**
     *  Return the parent if no override can be found
     *
     *  This fixes a bug in the template file locator
     *
     * @param array  $info      The path information
     * @return bool|mixed
     */
    public function find(array $info)
    {
        if(!$result = parent::find($info)) {
            $result = KTemplateLocatorFile::find($info);
        }

        return $result;
    }
}