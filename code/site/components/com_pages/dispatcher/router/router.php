<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouter extends ComPagesDispatcherRouterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'resolvers'  => array('page'),
        ));

        parent::_initialize($config);
    }

    public function getPage()
    {
        $page = false;

        if($route = $this->resolve()) {
            $page = $this->getObject('page.registry')->getPage($route->getPath());
        }

        return $page;
    }
}