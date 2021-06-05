<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

defined('_JEXEC') or die;
if (!class_exists('Koowa')) {
    return;
}

try {
    Koowa::getObject('com:pages.dispatcher.http')->dispatch();
} catch(Exception $exception) {
    Koowa::getObject('exception.handler')->handleException($exception);
}