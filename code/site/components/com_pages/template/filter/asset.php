<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */


class ComPagesTemplateFilterAsset extends ComKoowaTemplateFilterAsset
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_LOW,
            'schemes' => array('theme://' => 'base://templates/'.JFactory::getApplication()->getTemplate().'/'),
        ));

        parent::_initialize($config);
    }
}