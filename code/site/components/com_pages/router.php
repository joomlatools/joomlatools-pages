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
    $path = false;

    if($query['option'] == 'com_pages')
    {
        $router = KObjectManager::getInstance()
            ->getObject('com://site/pages.dispatcher.http')
            ->getRouter();


        if($router->resolve())
        {
            if($canonical = $router->getCanonicalUrl())
            {
                $query = array_merge($query, $canonical->query);
                $path  = $canonical->path;

                unset($query['view']);
            }
        }
    }

   return $path;
}