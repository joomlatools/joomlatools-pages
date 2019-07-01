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
            'priority' => self::PRIORITY_HIGHEST,
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
            $file = $route->getPath().'/config.php';

            //Configure object manager
            if(file_exists($file))
            {
                $config = $this->getObject('object.config.factory')->fromFile($file, false);

                if(file_exists(JPATH_CONFIGURATION.'/configuration-pages.php'))
                {
                    $default = (array) include JPATH_CONFIGURATION.'/configuration-pages.php';
                    $config = array_merge_recursive($default, $config);
                }

                $base_path = $route->getPath();

                //Add extension locators
                $this->getObject('manager')->getClassLoader()->registerLocator(new ComPagesClassLocatorExtension(array(
                    'namespaces' => array(
                        '\\'  => $base_path.'/extensions',
                    )
                )));

                $this->getObject('manager')->registerLocator('com:pages.object.locator.extension');

                //Load config options
                $path    = $this->getObject('object.bootstrapper')->getComponentPath('pages');
                $options = include $path.'/resources/config/options.php';

                //Set config options
                foreach($options['identifiers'] as $identifier => $values) {
                    $this->getConfig($identifier)->merge($values);
                }

                //Set config options
                foreach($options['extensions'] as $identifier => $values) {
                    $this->getConfig($identifier)->merge($values);
                }
            }

            //Set the page routes
            $routes = $this->getObject('page.registry')->getRoutes();
            $router->getResolver('page')->addRoutes($routes);

            //Set the redirect routes
            $routes = $this->getObject('page.registry')->getRedirects();
            $router->getResolver('redirect')->addRoutes($routes);

            //Add the cacheable behavior, if http cache is enabled
            if(isset($config['http_cache']) && $config['http_cache']) {
                $this->getObject('dispatcher')->addBehavior('cacheable');
            }

            //Configure the template
            if(isset($config['template']) || isset($config['template_config']))
            {
                if(isset($config['template'])) {
                    $template = $config['template'];
                } else {
                    $template = JFactory::getApplication()->getTemplate();
                }

                if(isset($config['template_config']) && is_array($config['template_config'])) {
                    $params = $config['template_config'];
                } else {
                    $params = null;
                }

                JFactory::getApplication()->setTemplate($template, $params);
            }

            //Register event subscribers
            foreach (glob($base_path.'/extensions/subscriber/[!_]*.php') as $filename) {
                $this->getObject('event.subscriber.factory')->registerSubscriber('ext:subscriber.'.basename($filename, '.php'));
            }

            //Register template filters
            foreach (glob($base_path.'/extensions/template/filter/[!_]*.php') as $filename) {
                $this->getConfig('com://site/pages.template.default')->merge(['filters' => ['ext:template.filter.'.basename($filename, '.php')]]);
            }

            //Register template function
            foreach (glob($base_path.'/extensions/template/function/[!_]*.php') as $filename) {
                $this->getConfig('com://site/pages.template.default')->merge(['functions' => [basename($filename, '.php') => $filename]]);
            }

        }

        return false;
    }

    public function generate($page, array $query, ComPagesDispatcherRouterInterface $router)
    {
        //Do not allow for reverse routing
        return false;
    }
}