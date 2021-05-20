<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

//Load config
$files = glob(JPATH_CONFIGURATION.'/*pages.php');
if(!empty($files) && file_exists($files[0])) {
    $config = (array) include $files[0];
} else {
    $config = array();
}

//Load config options
return [

    'priority' => KObjectBootstrapper::PRIORITY_HIGH,
    'aliases' => [
        'router'        => 'com:pages.dispatcher.router.factory',
        'page'          => 'com:pages.page',
        'pages.config'  => 'com:pages.config',
        'page.registry' => 'com:pages.page.registry',
        'data.registry' => 'com:pages.data.registry',
        'model.factory' => 'com:pages.model.factory',

        //Aliases for framework
        'lib:template.engine.koowa' => 'com:pages.template.engine.koowa',
        'lib:template.engine.twig ' => 'com:pages.template.engine.twig',

        //Aliases for com:koowa
        'com:koowa.template.filter.asset' => 'com:pages.template.filter.asset',
    ],

    'identifiers' => [
        'response' => [
            'headers'  => $config['headers'] ??  array(),
        ],
        'object.config.factory' => [
            'formats' => [
                'md'   => 'com:pages.object.config.markdown',
                'csv'  => 'com:pages.object.config.csv',
                'json' => 'com:pages.object.config.json',
                'xml'  => 'com:pages.object.config.xml',
                'html' => 'com:pages.object.config.html',
            ],
        ],
        'template.locator.factory' => [
            'locators' => [
                'com:pages.data.locator',
                'com:pages.page.locator',
                'com:pages.template.locator.theme',
                'com:pages.template.locator.template'
            ]
        ],
        'template.engine.factory' => [
            'engines' => [
                'lib:template.engine.markdown',
            ]
        ],
        'event.subscriber.factory' => [
            'subscribers' => [
                'com:pages.event.subscriber.bootstrapper',
                'com:pages.event.subscriber.dispatcher',
                'com:pages.event.subscriber.exception',
                'com:pages.event.subscriber.staticcache',
            ]
        ],
        'com:pages.dispatcher.router.site' => [
            'routes'  => isset($config['sites']) ? array_flip($config['sites']) : array(JPATH_ROOT.'/joomlatools-pages' => '[*]'),
        ],
    ]
];