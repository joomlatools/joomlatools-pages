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
            define('PAGES_SITE_ROOT', $route->getPath());

            //Restore phar stream wrapper
            @stream_wrapper_restore('phar');

            //Set PAGES_PATH based on Joomla configuration
            if(JFactory::getApplication()->getCfg('sef_rewrite')) {
                $_SERVER['PAGES_PATH'] = JFactory::getApplication()->getCfg('live_site') ?? '/';
            }

            //Set the site path in the config
            $config = $this->getObject('pages.config', ['site_path' => $route->getPath()]);

            //Get the config options
            $options = $config->getOptions();

            //Bootstrap the site configuration (before extensions to allow overriding)
            $this->_bootstrapSite($config->getSitePath(), $options);

            //Install the extensions
            $this->_installExtensions($config->getSitePath('extensions'), $options);

            //Update the extensions
            $this->_updateExtensions($config->getSitePath('extensions'), $options);

            //Archive the extensions
            $this->_archiveExtensions($config->getSitePath('extensions'), $options);

            //Bootstrap the extensions
            $this->_bootstrapExtensions($config->getSitePath('extensions'), $options);

        }
        else $this->getObject('pages.config', ['site_path' => false]);
    }

    public function onBeforeDispatcherDispatch(KEventInterface $event)
    {
        $config = $this->getObject('pages.config')->getOptions();

        //Configure the Joomla template
        if(isset($config['template']) || isset($config['template_config']))
        {
            if(isset($config['template'])) {
                $template = $config['template'];
            } else {
                $template = JFactory::getApplication()->getTemplate();
            }

            $params = JFactory::getApplication()->getTemplate(true)->params;
            if(isset($config['template_config']) && is_array($config['template_config']))
            {
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

        //Include autoloader
        include $directory.'/resources/vendor/autoload.php';

        //Set config options
        $options = include $directory.'/resources/config/site.php';

        //Set config options
        foreach($options['identifiers'] as $identifier => $values) {
            $this->getConfig($identifier)->merge($values);
        }

        //Set config options
        foreach($options['extensions'] as $identifier => $values) {
            $this->getConfig($identifier)->merge($values);
        }
    }

    protected function _installExtensions($path, $config = array())
    {
        if(file_exists($path.'/package.php')) {
            $packages = include $path.'/package.php';
        } else {
            $packages = array();
        }

        foreach($packages as $url)
        {
            $filepath  = trim(parse_url($url, PHP_URL_PATH), '/');
            $archive   = basename($filepath);
            $directory = basename($filepath, '.zip');

            if(!file_exists($path.'/'.$archive) && !file_exists($path.'/'.$directory))
            {
                $context = stream_context_create([
                    "ssl" => [
                        "verify_peer"      => false,
                        "verify_peer_name" => false,
                    ],
                ]);

                $log  = $path.'/package.log';
                $date = date('y:m:d h:i:s');
                if(copy($url, $path.'/'.$archive, $context))
                {
                    try
                    {
                        $phar = new PharData($path.'/'.$archive);
                        $metadata = $phar->getMetadata();

                        //Update the metadata
                        $metadata = $phar->getMetadata();
                        $metadata['url'] = $url;

                        $phar->setMetadata($metadata);

                        if(isset($metadata['version'])) {
                            error_log(sprintf('%s - Install Success: %s, version %s'."\n", $date, $url, $metadata['version']), 3, $log);
                        } else {
                            error_log(sprintf('%s - Install Success: %s'."\n", $date, $url), 3, $log);
                        }
                    }
                    catch(Exception $e) {
                        error_log(sprintf('%s - Install Failed: %s, error %s'."\n", $date, $url, $e->getMessage()), 3, $log);
                    }
                }
                else error_log(sprintf('%s - Install Failed: %s, error %s'."\n", $date, $url, $e->getMessage()), 3, $log);
            }
        }
    }

    protected function _updateExtensions($path, $config = array())
    {
        if(file_exists($path.'/package.php')) {
            $packages = include $path.'/package.php';
        } else {
            $packages = array();
        }

        foreach($packages as $url)
        {
            $filepath  = trim(parse_url($url, PHP_URL_PATH), '/');
            $archive   = basename($filepath);

            $phar = new PharData($path . '/' . $archive);
            $metadata = $phar->getMetadata();

            if(isset($metadata['url']) && $metadata['url'] !== $url)
            {
                $context = stream_context_create([
                    "ssl" => [
                        "verify_peer"      => false,
                        "verify_peer_name" => false,
                    ],
                ]);

                $log  = $path.'/package.log';
                $date = date('y:m:d h:i:s');
                if(copy($url, $path.'/'.$archive, $context))
                {
                    try
                    {
                        $phar = new PharData($path.'/'.$archive);

                        //Update the metadata
                        $metadata = $phar->getMetadata();
                        $metadata['url'] = $url;

                        $phar->setMetadata($metadata);

                        if(isset($metadata['version'])) {
                            error_log(sprintf('%s - Update Success: %s, version %s'."\n", $date, $url, $metadata['version']), 3, $log);
                        } else {
                            error_log(sprintf('%s - Update Success: %s'."\n", $date, $url), 3, $log);
                        }
                    }
                    catch(Exception $e) {
                        error_log(sprintf('%s - Update Failed: %s, error %s'."\n", $date, $url, $e->getMessage()), 3, $log);
                    }
                }
                else error_log(sprintf('%s - Update Failed: %s, error %s'."\n", $date, $url, $e->getMessage()), 3, $log);

            }
        }
    }

    protected function _archiveExtensions($path, $config = array())
    {
        if($files = glob($path.'/[!_]*/manifest.yaml'))
        {
            foreach($files as $file)
            {
                $name = basename(dirname($file));

                $log = $path . '/package.log';
                $date = date('y:m:d h:i:s');

                $size = function ($path) use (&$size)
                {
                    $result = array();

                    if (is_dir($path))
                    {
                        $files = array_diff(scandir($path), array('.', '..', '.DS_Store'));

                        foreach ($files as $file)
                        {
                            if (is_dir($path . '/' . $file)) {
                                $result[$file] = $size($path . '/' . $file);
                            } else {
                                $result[$file] = sprintf('%u', filesize($path . '/' . $file));
                            }
                        }
                    }
                    else $result[basename($path)] = sprintf('%u', filesize($path));

                    return $result;
                };

                //Load the manifest
                $manifest = $this->getObject('object.config.factory')->fromFile($file, false);

                $build   = $manifest['build']  ?? 0;
                $version = $manifest['version'] ?? 'unknown';

                $archive = 'create';
                $hash    = hash('crc32b', serialize($size($path . '/' . $name)));

                if (file_exists($path . '/' . $name . '.zip'))
                {
                    $phar = new PharData($path . '/' . $name . '.zip');
                    $metadata = $phar->getMetadata();

                    $build = $metadata['build'] ?? $build;
                    $version = $metadata['version'] ?? $version;

                    if (isset($metadata['hash']) && $metadata['hash'] == $hash) {
                        $archive = false;
                    } else {
                        $archive = 'update';
                    }
                }

                if ($archive)
                {
                    try
                    {
                        $phar = new PharData($path . '/' . $name . '.zip');
                        $phar->buildFromDirectory($path . '/' . $name);
                        $phar->compressFiles(Phar::GZ);
                        $phar->setSignatureAlgorithm(Phar::SHA256);
                        $phar->setMetadata([
                            'hash' => $hash,
                            'date' => $date,
                            'version' => $version,
                            'build'   => $build + 1
                        ]);

                        if ($archive == 'create') {
                            error_log(sprintf('%s - Archive Created: %s, version %s' . "\n", $date, $name . '.zip', $version), 3, $log);
                        } else {
                            error_log(sprintf('%s - Archive Updated: %s, version %s, build %s' . "\n", $date, $name . '.zip', $version, $build), 3, $log);
                        }

                    } catch (Exception $e) {
                        error_log(sprintf('%s - Archive Failed: %s, error $s' . "\n", $date, $name . '.zip', $e->getMessage()), 3, $log);
                    }
                }
            }
        }
    }

    protected function _bootstrapExtensions($path, $config = array())
    {
        //Register 'ext:[package]' locations
        if($directories = glob($path.'/[!_]*'))
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
                $name = strtolower(basename($directory, '.zip'));

                if(pathinfo($directory, PATHINFO_EXTENSION) == 'zip')
                {
                    if(!is_dir($path.'/'.$name)) {
                        $directory = 'phar://'.$directory;
                    } else {
                        continue;
                    }
                }

                //Register the extension namespace
                $locator->registerNamespace(ucfirst($name), $directory);

                //Register event subscribers
                if(is_dir($directory.'/event/subscriber'))
                {
                    foreach(scandir($directory.'/event/subscriber') as $filename)
                    {
                        if(!str_starts_with($filename, '_') && str_ends_with($filename, '.php'))
                        {
                            $this->getObject('event.subscriber.factory')
                                ->registerSubscriber('ext:'.$name.'.event.subscriber.'.basename($filename, '.php'));
                        }
                    }
                }

                //Find template functions
                if(is_dir($directory.'/template/function'))
                {
                    foreach(scandir($directory.'/template/function') as $filename)
                    {
                        if(!str_starts_with($filename, '_') && str_ends_with($filename, '.php')) {
                            $functions[basename($filename, '.php')] = $directory.'/template/function/'.$filename;
                        }
                    }
                }

                //Include autoloader
                if(file_exists($directory.'/resources/vendor/autoload.php')) {
                    include $directory.'/resources/vendor/autoload.php';
                }

                if(file_exists($directory.'/config.php'))
                {
                    $identifiers = include $directory.'/config.php';

                    if(is_array($identifiers))
                    {
                        foreach($identifiers as $identifier => $values) {
                            $this->getConfig($identifier)->merge($values);
                        }
                    }
                }
            }

            //Register template functions
            if($functions) {
                $this->getConfig('com://site/pages.template.default')->merge(['functions' => $functions]);
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