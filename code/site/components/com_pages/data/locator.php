<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDataLocator extends KTemplateLocatorFile
{
    protected static $_name = 'data';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'base_path' =>  $this->getObject('com:pages.config')->getSitePath('data'),
        ]);

        parent::_initialize($config);
    }
}