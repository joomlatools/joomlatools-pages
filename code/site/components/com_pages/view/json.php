<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewJson extends KViewJson
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'text_fields' => array('description', 'content') // Links are converted to absolute ones in these fields
        ));

        parent::_initialize($config);
    }
}