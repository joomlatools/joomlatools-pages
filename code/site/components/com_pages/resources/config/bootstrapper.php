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
        'router'        => 'com:pages.dispatcher.router',
        'page.registry' => 'com:pages.page.registry',
        'data.registry' => 'com:pages.data.registry',
        'com:pages.version'     => 'com://admin/pages.version',
        'com:pages.data.object' => 'com://site/pages.data.object',
        'com:pages.data.client' => 'com://site/pages.data.client',
        'com:pages.model.entity.page' => 'com://site/pages.model.entity.page',
    ],

    'identifiers' => [
        'response' => [
            'headers'  => $config['headers'] ??  array(),
        ],
        'object.config.factory' => [
            'formats' => ['md' => 'ComPagesObjectConfigMarkdown']
        ],
        'template.locator.factory' => [
            'locators' => [
                'com:pages.data.locator',
                'com:pages.page.locator',
                'com:pages.template.locator.theme'
            ]
        ],
        'template.engine.factory' => [
            'engines' => [
                'lib:template.engine.markdown',
            ]
        ],
        'event.subscriber.factory' => [
            'subscribers' => [
                'com:pages.event.subscriber.pagedecorator',
                'com:pages.event.subscriber.errorhandler',
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
        'com://site/pages.dispatcher.router.resolver.site' => [
            'routes'  => isset($config['sites']) ? array_flip($config['sites']) : array(JPATH_ROOT.'/joomlatools-pages' => '[*]'),
        ],
    ]
];