<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

return array(

    'identifiers' => [
        'template.locator.factory' => [
            'locators' => [
                'com:pages.data.locator',
                'com:pages.template.locator'
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