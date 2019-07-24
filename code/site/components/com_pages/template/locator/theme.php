<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateLocatorTheme extends KTemplateLocatorFile
{
    protected static $_name = 'theme';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'base_path' => $this->getObject('com:pages.config')->getSitePath('theme')
        ]);

        parent::_initialize($config);
    }
}