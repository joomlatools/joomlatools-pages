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
            $base = $this->getRequest()->getBasePath();
            $url  = urldecode( $this->getRequest()->getUrl()->getPath());

            //Strip script name if request is not rewritten (allow to redirect /index.php/)
            $route = str_replace(array($base, $this->getObject('pages.config')->getUrlPrefix()), '', $url);
        }

        return parent::resolve($route, $parameters);
    }
}