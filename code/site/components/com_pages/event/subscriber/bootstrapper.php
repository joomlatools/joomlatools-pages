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

        if(false !== $route = $router->resolve())
        {
            //Set the site path in the config
            $config = $this->getObject('com://site/pages.config', ['site_path' => $route->getPath()]);

            //Get the config options
            $options = $config->getOptions();

            //Bootstrap the site configuration
            $this->_bootstrapSite($config->getSitePath(), $options);

            //Bootstrap the extensions
            $this->_bootstrapExtensions($config->getSitePath('extensions'), $options);
        }
        else $this->getObject('com://site/pages.config', ['site_path' => false]);
    }

    public function onBeforeDispatcherDispatch(KEventInterface $event)
    {
        $config = $this->getObject('com://site/pages.config')->getOptions();

        //Configure the Joomla template
        if(isset($config['template']) || isset($config['template_config']))
        {
            if(isset($config['template'])) {
                $template = $config['template'];
            } else {
                $template = JFactory::getApplication()->getTemplate();
            }

            if(isset($config['template_config']) && is_array($config['template_config']))
            {
                $params = JFactory::getApplication()->getTemplate(true)->params;

                foreach($config['template_config'] as $name => $value) {
                    $params->set($name, $value);
                }
            }

            JFactory::getApplication()->setTemplate($template, $params);
        }
    }

    protected function _bootstrapSite($path, $config = array())
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

        //Register the composer class locator
        if(isset($options['composer_path']) && file_exists($options['composer_path']))
        {
            $this->getObject('manager')->getClassLoader()->registerLocator(
                new KClassLocatorComposer(array(
                        'vendor_path' => $options['composer_path']
                    )
                ));
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