<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouter extends ComPagesDispatcherRouterAbstract implements KObjectSingleton
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Add a global object alias
        $this->getObject('manager')->registerAlias($this->getIdentifier(), 'router');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'resolvers'  => array('site', 'page'),
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