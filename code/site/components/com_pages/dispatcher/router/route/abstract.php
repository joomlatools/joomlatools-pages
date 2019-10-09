<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Abstract Router Route
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Route
 */
abstract class ComPagesDispatcherRouterRouteAbstract extends KHttpUrl implements ComPagesDispatcherRouterRouteInterface
{
    /**
     * Get the resolver identifier
     *
     * @return KObjectIdentifierInterface
     */
    public function getResolver()
    {
        $identifier = $this->getIdentifier()->toArray();

        if($identifier['package'] != 'dispatcher') {
            $identifier['path'] = array('dispatcher', 'router');
        } else {
            $identifier['path'] = array('router');
        }

        $identifier['package'] = $this->getScheme();

        if($host = $this->getHost())
        {
            $identifier['path'][] = 'resolver';
            $identifier['name']   = $this->getHost();
        }
        else $identifier['name'] = 'resolver';

        return $this->getIdentifier($identifier);
    }
}