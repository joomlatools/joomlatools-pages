<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesObjectLocatorExtension extends KObjectLocatorAbstract
{
    protected static $_name = 'ext';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'sequence' => array(
                'Ext<Package><Path><File>',
            )
        ));

        parent::_initialize($config);
    }
}
