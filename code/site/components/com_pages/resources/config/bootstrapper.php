<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return array(

    'aliases' => [
        'page.registry' => 'com:pages.page.registry',
        'data.registry' => 'com:pages.data.registry'
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
        'lib:template.engine.markdown' => [
            'compiler' => function($text) {
                //See: https://michelf.ca/projects/php-markdown/extra/
                return \Michelf\MarkdownExtra::defaultTransform($text);
            }
        ],
    ]
);