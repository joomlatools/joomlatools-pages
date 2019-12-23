<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberBootstrapper extends ComPagesEventSubscriberAbstract
{
    protected $_config;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function onAfterKoowaBootstrap(KEventInterface $event)
    {
        $request = $this->getObject('request');
        $router  = $this->getObject('com://site/pages.dispatcher.router.site', ['request' => $request]);

        if(false === $route = $router->resolve()) {
            throw new KHttpExceptionNotFound('Site Not Found');
        }

        //Set the site path in the config
        $config = $this->getObject('com://site/pages.config', ['site_path' => $route->getPath()]);

        //Load the configuration
        $this->_config = $this->_loadConfig($config->getSitePath());

        //Bootstrap the site configuration
        $this->_bootstrapIdentifiers($config->getSitePath(), $this->_config);

        //Bootstrap the extensions
        $this->_bootstrapExtensions($config->getSitePath('extensions'), $this->_config);
    }

    public function onBeforeDispatcherDispatch(KEventInterface $event)
    {
        //Configure the Joomla template
        if(isset($this->_config['template']) || isset($this->_config['template_config']))
        {
            if(isset($config['template'])) {
                $template = $this->_config['template'];
            } else {
                $template = JFactory::getApplication()->getTemplate();
            }

            if(isset($config['template_config']) && is_array($this->_config['template_config'])) {
                $params = $this->_config['template_config'];
            } else {
                $params = null;
            }

            JFactory::getApplication()->setTemplate($template, $params);
        }
    }

    protected function _loadConfig($path)
    {
        $config = array();

        //Load default config
        if(file_exists(JPATH_CONFIGURATION.'/configuration-pages.php')) {
            $config = (array) include JPATH_CONFIGURATION.'/configuration-pages.php';
        }

        //Load site config
        $file =  $path.'/config.php';
        if(file_exists($file))
        {
            //Load config
            $site   = $this->getObject('object.config.factory')->fromFile($file, false);
            $config = array_merge_recursive($site, $config);
        }

        return $config;
    }

    protected function _bootstrapIdentifiers($path, $config = array())
    {
        //Load config options
        $base_path = $path;

        $directory = $this->getObject('object.bootstrapper')->getComponentPath('pages');
        $options   = include $directory.'/resources/config/site.php';

        //Set config options
        foreach($options['identifiers'] as $identifier => $values) {
            $this->getConfig($identifier)->merge($values);
        }

        //Set config options
        foreach($options['extensions'] as $identifier => $values) {
            $this->getConfig($identifier)->merge($values);
        }
    }

    protected function _bootstrapExtensions($path, $config = array())
    {
        //Add extension locators
        $this->getObject('manager')->getClassLoader()->registerLocator(new ComPagesClassLocatorExtension(array(
            'namespaces' => array('\\'  => $path)
        )));

        $this->getObject('manager')->registerLocator('com://site/pages.object.locator.extension');

        //Register event subscribers
        foreach (glob($path.'/subscriber/[!_]*.php') as $filename) {
            $this->getObject('event.subscriber.factory')->registerSubscriber('ext:subscriber.'.basename($filename, '.php'));
        }

        //Register template filters
        $filters = array();
        foreach (glob($path.'/template/filter/[!_]*.php') as $filename) {
            $filters[] = 'ext:template.filter.'.basename($filename, '.php');
        }

        if($filters) {
            $this->getConfig('com://site/pages.template.default')->merge(['filters' => $filters]);
        }

        //Register template functions
        $functions = array();
        foreach (glob($path.'/template/function/[!_]*.php') as $filename) {
            $functions[basename($filename, '.php')] = $filename;
        }

        if($functions) {
            $this->getConfig('com://site/pages.template.default')->merge(['functions' => $functions]);
        }
    }
}