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
            define('JPATH_PAGES', $route->getPath());

            //Set the site path in the config
            $config = $this->getObject('com://site/pages.config', ['site_path' => $route->getPath()]);

            //Get the config options
            $options = $config->getOptions();

            //Bootstrap the extensions
            $this->_bootstrapExtensions($config->getSitePath('extensions'), $options);

            //Bootstrap the site configuration (after extensions to allow overriding)
            $this->_bootstrapSite($config->getSitePath(), $options);
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
        //Register 'ext:[package]' locations
        if($directories = glob($path.'/*', GLOB_ONLYDIR))
        {
            //Register 'ext' fallback location
            $locator = new ComPagesClassLocatorExtension();

            //Register the extension locator
            $this->getObject('manager')->getClassLoader()->registerLocator($locator);
            $this->getObject('manager')->registerLocator('com://site/pages.object.locator.extension');

            $filters    = array();
            $functions  = array();

            foreach ($directories as $directory)
            {
                //The extension name
                $name = strtolower(basename($directory));

                //Register the extension namespace
                $locator->registerNamespace(ucfirst($name), $directory);

                //Register event subscribers
                foreach (glob($directory.'/subscriber/[!_]*.php') as $filename)
                {
                    $this->getObject('event.subscriber.factory')
                        ->registerSubscriber('ext:'.$name.'.subscriber.'.basename($filename, '.php'));
                }

                //Find template filters
                foreach (glob($directory.'/template/filter/[!_]*.php') as $filename) {
                    $filters[] = 'ext:'.$name.'.template.filter.'.basename($filename, '.php');
                }

                //Find template functions
                foreach (glob($directory.'/template/function/[!_]*.php') as $filename) {
                    $functions[basename($filename, '.php')] = $filename;
                }

                //Set config options
                if(file_exists($directory.'/config.php'))
                {
                    $identifiers = include $directory.'/config.php';

                    foreach($identifiers as $identifier => $values) {
                        $this->getConfig($identifier)->merge($values);
                    }
                }
            }

            //Register template functions
            if($functions) {
                $this->getConfig('com://site/pages.template.default')->merge(['functions' => $functions]);
            }

            //Register template filters
            if($filters) {
                $this->getConfig('com://site/pages.template.default')->merge(['filters' => $filters]);
            }
        }

        //Register 'ext:pages' aliases
        if(file_exists($path.'/pages'))
        {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.'/pages'));

            foreach($iterator as $file)
            {
                if ($file->isFile() && $file->getExtension() == 'php' && $file->getFileName() !== 'config.php')
                {
                    $segments = explode('/', $iterator->getSubPathName());
                    $segments[] = basename(array_pop($segments), '.php');

                    //Create the identifier path + file
                    $path = implode('.', $segments);

                    $this->getObject('manager')->registerAlias(
                        'ext:pages.'.$path,
                        'com://site/pages.'.$path
                    );
                }
            }
        }
    }
}