<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

return array(

    'aliases'  => [
        'com:koowa.template.locator.file' => 'com:pages.template.locator.file'
    ],

    'identifiers' => [
        'template.locator.factory' => [
            'locators' => [
                'com:pages.template.locator.page'
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
        ]
    ]
);