<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Router File Route
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Route
 */
class ComPagesDispatcherRouterRouteFile extends ComPagesDispatcherRouterRouteAbstract
{
    public function isAbsolute()
    {
        return file_exists($this->getPath());
    }
}