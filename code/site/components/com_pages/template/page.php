<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplatePage extends KTemplate
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'filters'   => array('markdown'),
            'functions' => array(
                'data' => function($path, $format = '') {
                    return  $this->getObject('com:pages.data.factory')->createObject($path, $format);
                },

            ),
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types' => array('html', 'txt', 'svg', 'css', 'js'),
        ));

        parent::_initialize($config);
    }
}