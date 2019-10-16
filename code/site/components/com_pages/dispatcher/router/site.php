<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterSite extends ComPagesDispatcherRouterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'routes' => [],
        ])->append(([
            'resolvers' => [
                'regex' => ['routes' => $config->routes]
            ]
        ]));

        parent::_initialize($config);
    }
}