<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return [
    'ext:images.template.helper.image'   => [
        'exclude'   => ['gif'],
        'suffix'    => '',
        'max_width' => 1920,
        'base_path' => KPATH_PAGES,
    ],
    'ext:pages.template.filter.image'   => [
        'enable'    => JDEBUG ? false : true,
    ],
];