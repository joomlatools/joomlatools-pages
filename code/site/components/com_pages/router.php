<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 *  Return the canoncial url to Joomla when asked
 * See: PlgSystemSef::onAfterRoute()
 */

function PagesBuildRoute(&$query)
{
    $url = KObjectManager::getInstance()->getObject('com:pages.dispatcher.http')->getRouter()->getCanonicalUrl();

    $query = array_merge($query, $url->query);
    $path  = $url->path;

    return $path;
}