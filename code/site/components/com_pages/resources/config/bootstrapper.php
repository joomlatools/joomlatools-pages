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

    'priority' => KObjectBootstrapper::PRIORITY_HIGH,
    'aliases' => [
        'router'        => 'com:pages.dispatcher.router',
        'page.registry' => 'com:pages.page.registry',
        'data.registry' => 'com:pages.data.registry',
        'com:pages.version'     => 'com://admin/pages.version',
        'com:pages.data.object' => 'com://site/pages.data.object',
        'com:pages.data.client' => 'com://site/pages.data.client',
    ],

    'identifiers' => [
        'page.registry' => [
            'cache'       => $config['page_cache'] ?? (JDEBUG ? false : true),
            'cache_time'  => $config['page_cache_time'] ?? 60*60*24, //1d
            'cache_path'  => $config['page_cache_path'] ?? null,
            'collections' => $config['collections'] ?? array(),
        ],
        'data.registry' => [
            'cache'      => $config['data_cache'] ?? (JDEBUG ? false : true),
            'cache_time' => $config['data_cache_time'] ?? 60*60*24, //1d
            'cache_path' => $config['data_cache_path'] ?? null
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
            'cache'      => $config['template_cache'] ?? (JDEBUG ? false : true),
            'cache_path' => $config['template_cache_path'] ?? JPATH_ADMINISTRATOR.'/cache/koowa.templates',
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
        'com://site/pages.template.filter.highlight' => [
            'highlighter' => function($source, $language) {
                //See: https://github.com/scrivo/highlight.php
                return (new \Highlight\Highlighter())->highlight($language, $source, false)->value;
            }
        ],
        'com://site/pages.dispatcher.behavior.cacheable' => [
            'cache'             => $config['http_cache'] ?? false,
            'cache_path'        => $config['http_cache_path'] ?? null,
            'cache_time'        => $config['http_cache_time']       ?? 60*15,  //15min
            'cache_time_shared' => $config['http_cache_time_proxy'] ?? 60*60*2, //2h
        ],
        'com://site/pages.dispatcher.router.resolver.redirect' => [
            'routes'  => isset($config['redirects']) ? array_flip($config['redirects']) : false,
        ],
        'com://site/pages.data.client' => [
            'cache'      => $config['remote_cache'] ?? (JDEBUG ? false : true),
            'cache_time' => $config['remote_cache_time'] ?? 60*60*24, //1d
            'cache_path' => $config['remote_cache_path'] ?? null
        ],
    ]
);