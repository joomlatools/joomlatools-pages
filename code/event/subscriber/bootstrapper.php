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
            'priority' => KEvent::PRIORITY_HIGH,
            'log_path' => null,
        ));

        parent::_initialize($config);
    }

    public function onAfterKoowaBootstrap(KEventInterface $event)
    {
        define('PAGES_VERSION', (string) $this->getObject('com:pages.version'));

        //Route the site
        if(!defined('PAGES_SITE_ROOT') || empty(PAGES_SITE_ROOT))
        {
            $request = $this->getObject('request');
            $router  = $this->getObject('com:pages.dispatcher.router.site', ['request' => $request]);

            if(false !== $route = $router->resolve()) {
                define('PAGES_SITE_ROOT', $route->getPath());
            }
        }

        if(defined('PAGES_SITE_ROOT') && file_exists(PAGES_SITE_ROOT))
        {
            //Set the site path in the config
            $config = $this->getObject('pages.config', ['site_path' => PAGES_SITE_ROOT]);

            //Get the config options
            $options = $config->getOptions();

            //Bootstrap the site configuration (before extensions to allow overriding)
            $this->_bootstrapSite($config->getSitePath(), $options);

            //Bootstrap Joomla
            $this->_bootstrapJoomla($config->getSitePath(), $options);

            //Bootstrap extensions

            //Register 'ext' fallback location
            $locator = new ComPagesClassLocatorExtension();

            //Register the extension locator
            $this->getObject('manager')->getClassLoader()->registerLocator($locator);
            $this->getObject('manager')->registerLocator('com:pages.object.locator.extension');

            foreach($config->getExtensionPath() as $path)
            {
                //Install
                $this->_installExtensions($path);

                //Update
                $this->_updateExtensions($path);

                //Archive
                $this->_archiveExtensions($path);

                //Bootstrap
                $this->_bootstrapExtensions($path, $locator);
            }
        }
        else $this->getObject('pages.config', ['site_path' => false]);
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
        foreach($options['extension_config'] as $identifier => $values) {
            $this->getConfig($identifier)->merge($values);
        }
    }

    protected function _bootstrapJoomla($path, $config = array())
    {
        if(class_exists('\Joomla\CMS\Component\ComponentHelper'))
        {
            //Restore phar stream wrapper (Joomla uses the TYPO3 wrapper)
            @stream_wrapper_restore('phar');

            //Add com_pages to Joomla components
            $install = Closure::bind(function()
            {
                static::$components['com_pages'] = new Joomla\CMS\Component\ComponentRecord([
                    'option'  => 'com_pages',
                    'enabled' => 1
                ]);

            }, null, '\Joomla\CMS\Component\ComponentHelper');

            $install();
        }
    }

    protected function _installExtensions($path)
    {
        if(file_exists($path.'/package.php')) {
            $packages = (array) include $path.'/package.php';
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

                if($this->getConfig()->log_path) {
                    $log  = $this->getConfig()->log_path.'/extension.log';
                } else {
                    $log  = $this->getObject('com:pages.config')->getLogPath().'/extension.log';
                }

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

    protected function _updateExtensions($path)
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

                if($this->getConfig()->log_path) {
                    $log  = $this->getConfig()->log_path.'/extension.log';
                } else {
                    $log  = $this->getObject('com:pages.config')->getLogPath().'/extension.log';
                }

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

    protected function _archiveExtensions($path)
    {
        if($files = glob($path.'/[!_]*/manifest.yaml'))
        {
            foreach($files as $file)
            {
                $name = basename(dirname($file));

                if($this->getConfig()->log_path) {
                    $log  = $this->getConfig()->log_path.'/extension.log';
                } else {
                    $log  = $this->getObject('com:pages.config')->getLogPath().'/extension.log';
                }

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

    protected function _bootstrapExtensions($path, $locator)
    {
        //Register 'ext:[package]' locations
        if($directories = glob($path.'/[!_]*'))
        {
            $functions  = array();

            foreach ($directories as $directory)
            {
                //The extension name
                $name = strtolower(basename($directory, '.zip'));

                //Do not re-register the same extension
                if($locator->getNamespace(ucfirst($name))) {
                    continue;
                }

                if(pathinfo($directory, PATHINFO_EXTENSION) == 'zip')
                {
                    if(!is_dir($path.'/'.$name))
                    {
                        if(!class_exists('Phar')) {
                            throw new RuntimeException('Phar extension not available');
                        }

                        $directory = 'phar://'.$directory;
                    }
                    else continue;
                }

                //Get extension name from manifest
                if(file_exists($directory.'/manifest.yaml'))
                {
                    $manifest = $this->getObject('object.config.factory')->fromFile($directory.'/manifest.yaml', false);
                    $name = $manifest['name'] ?? $name;
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

                //Register 'ext:pages' aliases
                if($name == 'pages')
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
                                'com:pages.'.$path
                            );
                        }
                    }
                }
            }

            //Register template functions
            if($functions) {
                $this->getConfig('com:pages.template')->merge(['functions' => $functions]);
            }
        }
    }
}