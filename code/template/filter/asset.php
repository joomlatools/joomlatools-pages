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
        $site_path = $this->getObject('pages.config')->getSitePath();
        $root_path = Koowa::getInstance()->getRootPath();

        $config->append(array(
            'priority' => self::PRIORITY_LOW,
            'schemes' => array(
                'host://'   =>  $this->getObject('request')->getBaseUrl()->toString(KHttpUrl::AUTHORITY),
                'theme://'  => 'site://theme/',
                'site://'   => 'base://'.trim(str_replace($root_path, '', $site_path), '/').'/',
            ),
        ));

        parent::_initialize($config);
    }
}