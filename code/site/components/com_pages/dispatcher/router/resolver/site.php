<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterResolverSite extends ComPagesDispatcherRouterResolverAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_HIGH,
        ));

        parent::_initialize($config);
    }

    public function getPath(ComPagesDispatcherRouterInterface $router)
    {
        return ltrim($router->getResponse()->getRequest()->getUrl()->toString(KHttpUrl::HOST + KHttpUrl::PATH), '/');
    }

    public function resolve(ComPagesDispatcherRouterInterface $router)
    {
        if($route = parent::resolve($router))
        {
            $this->getObject('com:pages.object.manager.options', array('path' => $route->getPath()))->configure();

            //Configure page resolver
            $routes = $this->getObject('page.registry')->getRoutes();
            $router->getResolver('page')->addRoutes($routes);

            //Configure redirect resolver
            /*$routes = $this->getObject('page.registry')->getRedirects();
            $router->getResolver('redirect')->addRoutes($routes);*/
        }

        return false;
    }

    public function generate($page, array $query, ComPagesDispatcherRouterInterface $router)
    {
        //Do not allow for reverse routing
        return false;
    }
}