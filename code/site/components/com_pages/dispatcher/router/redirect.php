<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterRedirect extends ComPagesDispatcherRouterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'routes' => $this->getObject('page.registry')->getRedirects(),
        ])->append([
            'resolvers' => [
                'regex' => ['routes' => $config->routes]
            ]
        ]);

        parent::_initialize($config);
    }

    public function resolve($route = null, array $parameters = array())
    {
        if(!$route)
        {
            $base   = $this->getRequest()->getBasePath();
            $url    = urldecode( $this->getRequest()->getUrl()->getPath());

            $route  = trim($url, '/');
        }

        return parent::resolve($route, $parameters);
    }

    public function qualify(ComPagesDispatcherRouterRouteInterface $route,  $replace = false)
    {
        $url = clone $route;

        if($replace || !$route->isAbsolute())
        {
            $request = $this->getRequest();

            //Qualify the url
            $url->setUrl($request->getUrl()->toString(KHttpUrl::AUTHORITY));

            //Add index.php
            $base = $request->getBasePath();
            $path = trim($url->getPath(), '/');

            $url->setPath($base.'/'.$path);
        }

        return $url;
    }
}