<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewRss extends ComPagesViewXml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'data'     => array(
                'update_period'    => 'daily',
                'update_frequency' => 1
            )
        ));

        parent::_initialize($config);
    }
}