<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

//Load config
if(file_exists(JPATH_CONFIGURATION.'/configuration-pages.php')) {
    $config = (array) include JPATH_CONFIGURATION.'/configuration-pages.php';
} else {
    $config   = array();
}

//Load config options
return [

    'priority' => KObjectBootstrapper::PRIORITY_HIGH,
    'aliases' => [
        'router'        => 'com://site/pages.dispatcher.router',
        'page.registry' => 'com://site/pages.page.registry',
        'data.registry' => 'com://site/pages.data.registry',
        'com://site/pages.version' => 'com://admin/pages.version',
    ],

    'identifiers' => [
        'response' => [
            'headers'  => $config['headers'] ??  array(),
        ],
        'object.config.factory' => [
            'formats' => [
                'md'      => 'ComPagesObjectConfigMarkdown',
                'csv'     => 'ComPagesObjectConfigCsv',
                'jsonapi' => 'ComPagesObjectConfigJsonapi',
            ],
        ],
        'template.locator.factory' => [
            'locators' => [
                'com://site/pages.data.locator',
                'com://site/pages.page.locator',
                'com://site/pages.template.locator.theme'
            ]
        ],
        'template.engine.factory' => [
            'engines' => [
                'lib:template.engine.markdown',
            ]
        ],
        'event.subscriber.factory' => [
            'subscribers' => [
                'com://site/pages.event.subscriber.pagedecorator',
                'com://site/pages.event.subscriber.errorhandler',
                'com://site/pages.event.subscriber.bootstrapper',
            ]
        ],
        'lib:template.engine.markdown' => [
            'compiler' => function($text) {
                //See: https://michelf.ca/projects/php-markdown/extra/
                return \Michelf\MarkdownExtra::defaultTransform($text);
            }
        ],
        'com://site/pages.template.filter.highlight' => [
            'highlighter' => function($source, $language) {
                //See: https://github.com/scrivo/highlight.php
                return (new \Highlight\Highlighter())->highlight($language, $source, false)->value;
            }
        ],
        'com://site/pages.dispatcher.router.site' => [
            'routes'  => isset($config['sites']) ? array_flip($config['sites']) : array(JPATH_ROOT.'/joomlatools-pages' => '[*]'),
        ],
    ]
];