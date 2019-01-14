<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

class ComPagesDispatcherContext extends KDispatcherContext implements KDispatcherContextInterface
{
    public function getRouter()
    {
        return KObjectConfig::get('router');
    }

    public function setRouter(KDispatcherRouterInterface $router)
    {
        return KObjectConfig::set('router', $router);
    }
}