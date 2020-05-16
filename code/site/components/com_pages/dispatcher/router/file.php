<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterFile extends ComPagesDispatcherRouterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'routes' => []
        ])->append([
            'resolvers' => [
                'regex' => ['routes' => $config->routes]
            ]
        ]);

        parent::_initialize($config);
    }

    public function resolve($route = null, array $parameters = array())
    {
        $result = false;
        if(count($this->getConfig()->routes))
        {
            if(!$route)
            {
                $base       = $this->getRequest()->getBasePath();
                $url        = urldecode( $this->getRequest()->getUrl()->getPath());
                $parameters = $this->getRequest()->getUrl()->getQuery(true);

                $route  = trim(str_replace(array($base, '/index.php'), '', $url), '/');
            }

            $result = parent::resolve($route, $parameters);
        }

        return $result;
    }

    public function qualify(ComPagesDispatcherRouterRouteInterface $route,  $replace = false)
    {
        $url = clone $route;

        $path = $url->getPath();
        if(!is_file($path))
        {
            //Qualify the path
            $path = trim($path, '/');
            $base = $this->getRequest()->getBasePath(true);
            $url->setPath($base.'/'.$path);
        }

        return $url;
    }
}