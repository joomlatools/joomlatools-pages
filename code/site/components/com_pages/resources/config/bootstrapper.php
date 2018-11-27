<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

$config = array();
if(file_exists(Koowa::getInstance()->getRootPath().'/joomlatools-pages/config.php')) {
    $config = (array) include Koowa::getInstance()->getRootPath().'/joomlatools-pages/config.php';
}

return array(

    'aliases' => [
        'page.registry' => 'com:pages.page.registry',
        'data.registry' => 'com:pages.data.registry',
        'com:pages.version'     => 'com://admin/pages.version',
        'com:pages.data.object' => 'com://site/pages.data.object',
    ],

    'identifiers' => [
        'object.config.factory' => [
            'formats' => ['md' => 'ComPagesDataMarkdown']
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
            'subscribers' => ['com:pages.event.subscriber.decorator']
        ],
        'lib:template.engine.markdown' => [
            'compiler' => function($text) {
                //See: https://michelf.ca/projects/php-markdown/extra/
                return \Michelf\MarkdownExtra::defaultTransform($text);
            }
        ],
        'com:pages.template.filter.highlight' => [
            'highlighter'  => function($source, $language) {
                //See: https://github.com/scrivo/highlight.php
                return (new \Highlight\Highlighter())->highlight($language, $source, false)->value;
            }
        ],
        'com://site/pages.dispatcher.behavior.cacheable' => [
            'cache'         => $config['cache'] ?? false,
            'cache_time'    => $config['cache_time'] ?? 0,
            'cache_private' => $config['cache_private'] ?? false,
        ],

    ]
);