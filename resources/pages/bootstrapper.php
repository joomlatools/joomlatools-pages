<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */


if(!defined('KOOWA_ROOT')) {
    define('KOOWA_ROOT', realpath(getcwd()));
}

if(!defined('KOOWA_BASE')) {
    define('KOOWA_BASE', KOOWA_ROOT);
}

if(!defined('KOOWA_VENDOR')) {
    define('KOOWA_VENDOR' , KOOWA_ROOT.'/vendor');
}

if(!defined('KOOWA_CONFIG')) {
    define('KOOWA_CONFIG' , KOOWA_ROOT.'/config');
}

if(!defined('KOOWA_DEBUG')) {
    define('KOOWA_DEBUG' , false);
}

if(!defined('PAGES_SITE_ROOT')) {
    define('PAGES_SITE_ROOT', realpath(getcwd()));
}

//Load composer
require_once KOOWA_VENDOR.'/autoload.php';

//Load Component
require_once KOOWA_VENDOR.'/joomlatools/framework/code/libraries/joomlatools/component/koowa/koowa.php';

//Load Framework
require_once KOOWA_VENDOR.'/joomlatools/framework/code/libraries/joomlatools/library/koowa.php';

Koowa::getInstance(array(
    'root_path'    => KOOWA_ROOT,
    'vendor_path'  => KOOWA_VENDOR,
    'debug'        => KOOWA_DEBUG,
    'cache'        => KOOWA_DEBUG ? false : true,
));

//Bootstrap Framework
Koowa::getObject('object.bootstrapper')
    ->registerComponent('koowa', KOOWA_VENDOR.'/joomlatools/framework/code/libraries/joomlatools/component/koowa', 'koowa')
    ->registerComponent('pages', KOOWA_VENDOR.'/joomlatools/pages/code', 'koowa')
    ->bootstrap();

Koowa::getObject('event.publisher')
    ->publishEvent('onAfterKoowaBootstrap');

//Dispatch Pages component
Koowa::getObject('com:pages.dispatcher.http')
    ->dispatch();