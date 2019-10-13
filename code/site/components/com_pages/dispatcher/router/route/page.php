<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Router Page Route
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Route
 */
class ComPagesDispatcherRouterRoutePage extends ComPagesDispatcherRouterRouteAbstract
{
    public function getPage($content = false)
    {
        $page = false;

        if($path = $this->getPath()) {
            $page = $this->getObject('page.registry')->getPage($path, $content);
        }

        return $page;
    }
}