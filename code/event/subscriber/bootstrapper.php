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
            'log_path'     => null,
            'install_path' => null,
            'archive_path' => null,
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
            $options = $config->toArray();

            //Bootstrap the site configuration (before extensions to allow overriding)
            $this->_bootstrapSite($config->getSitePath(), $options);

            //Bootstrap extensions
            $this->_bootstrapExtensions($config->getExtensionPath(), $config);

        }
        else $config = $this->getObject('pages.config', ['site_path' => false]);

        $this->getObject('event.publisher')->publishEvent('onAfterPagesBootstrap', ['config' => $config->getConfig()]);
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

    protected function _bootstrapExtensions($extensions, $config)
    {
        //Restore phar stream wrapper (Joomla uses the TYPO3 wrapper)
        @stream_wrapper_restore('phar');

        //Register 'ext' fallback location
        $locator = new ComPagesClassLocatorExtension();

        //Register the extension locator
        $this->getObject('manager')->getClassLoader()->registerLocator($locator);
        $this->getObject('manager')->registerLocator('com:pages.object.locator.extension');

        $install_path = $config->getInstallPath();
        $archive_path = $config->getArchivePath();

        //Bootstrap Extensions
        if($install_path) {
            array_unshift($extensions, $install_path);
        }

        foreach(array_unique($extensions) as $path)
        {
            //Install Extensions
            if(substr($path, 0, 4) == 'http' && pathinfo($path, PATHINFO_EXTENSION) == 'zip')
            {
                $url = $path;

                //Try to update or install the extension
                if(!$path = $this->_updateExtension($url, $install_path)) {
                    $path = $this->_installExtension($url, $install_path);
                }

                //Extension not updated or installed skip bootstrapping
                if(!$path) {
                    continue;
                }
            }

            //Bootstrap Extensions
            if(!is_file($path) && !file_exists($path.'/manifest.yaml'))
            {
                foreach((array) glob($path.'/[!_]*') as $extension)
                {
                    //Do not bootstrap extension from zip if directory exists
                    if(pathinfo($extension, PATHINFO_EXTENSION) == 'zip')
                    {
                        $name = strtolower(basename($extension, '.zip'));
                        if(is_dir($path.'/'.$name)) {
                            continue;
                        }
                    }

                    $this->_bootstrapExtension($extension, $locator);
                    if(dirname($extension) == $install_path) {
                        $this->_archiveExtension($extension, $archive_path);
                    }
                }
            }
            else
            {
                $this->_bootstrapExtension($path, $locator);
                if(dirname($path) == $install_path) {
                    $this->_archiveExtension($path, $archive_path);
                }
            }
        }
    }

    protected function _bootstrapExtension($path, $locator)
    {
        $result    = false;
        $functions = array();

        //The extension name
        $name = strtolower(basename($path, '.zip'));

        //Do not re-register the same extension
        if(!$locator->getNamespace(ucfirst($name)))
        {
            if(pathinfo($path, PATHINFO_EXTENSION) == 'zip')
            {
                if(!class_exists('Phar')) {
                    throw new RuntimeException('Phar extension not available');
                } else {
                    $path = 'phar://'.$path;
                }
            }

            //Get extension name from manifest
            if(file_exists($path.'/manifest.yaml'))
            {
                $manifest = $this->getObject('object.config.factory')->fromFile($path.'/manifest.yaml', false);
                $name = $manifest['name'] ?? $name;
            }

            //Register the extension namespace
            $locator->registerNamespace(ucfirst($name), $path);

            //Register event subscribers
            if(is_dir($path.'/event/subscriber'))
            {
                foreach(scandir($path.'/event/subscriber') as $filename)
                {
                    if(!str_starts_with($filename, '_') && str_ends_with($filename, '.php'))
                    {
                        $this->getObject('event.subscriber.factory')
                            ->registerSubscriber('ext:'.$name.'.event.subscriber.'.basename($filename, '.php'));
                    }
                }
            }

            //Find template functions
            if(is_dir($path.'/template/function'))
            {
                foreach(scandir($path.'/template/function') as $filename)
                {
                    if(!str_starts_with($filename, '_') && str_ends_with($filename, '.php')) {
                        $functions[basename($filename, '.php')] = $path.'/template/function/'.$filename;
                    }
                }
            }

            //Include autoloader
            if(file_exists($path.'/resources/vendor/autoload.php')) {
                include $path.'/resources/vendor/autoload.php';
            }

            if(file_exists($path.'/config.php'))
            {
                $identifiers = include $path.'/config.php';

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
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

                foreach($iterator as $file)
                {
                    if ($file->isFile() && $file->getExtension() == 'php' && $file->getFileName() !== 'config.php')
                    {
                        $segments = explode('/', $iterator->getSubPathName());
                        $segments[] = basename(array_pop($segments), '.php');

                        //Create the identifier path + file
                        $identifier = implode('.', $segments);

                        $this->getObject('manager')->registerAlias(
                            'ext:pages.'.$identifier,
                            'com:pages.'.$identifier
                        );
                    }
                }
            }

            //Register template functions
            if($functions) {
                $this->getConfig('com:pages.template')->merge(['functions' => $functions]);
            }

            $result = true;
        }

        return $result;
    }

    protected function _installExtension($url, $destination)
    {
        $result = false;
        if($this-_canInstall($destination))
        {
            $filepath  = trim(parse_url($url, PHP_URL_PATH), '/');
            $archive   = basename($filepath);
            $directory = basename($filepath, '.zip');

            if(!file_exists($destination.'/'.$archive) && !file_exists($destination.'/'.$directory))
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
                if(copy($url, $destination.'/'.$archive, $context))
                {
                    try
                    {
                        $phar = new PharData($destination.'/'.$archive);
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

                        $result =  $destination.'/'.$archive;
                    }
                    catch(Exception $e) {
                        error_log(sprintf('%s - Install Failed: %s, error %s'."\n", $date, $url, $e->getMessage()), 3, $log);
                    }
                }
                else error_log(sprintf('%s - Install Failed: %s, could not copy from url'."\n", $date, $url), 3, $log);
            }
        }

        return $result;
    }

    protected function _updateExtension($url, $destination)
    {
        $result = false;

        if($this->_canInstall($destination))
        {
            $filepath  = trim(parse_url($url, PHP_URL_PATH), '/');
            $archive   = basename($filepath);

            $phar = new PharData($destination . '/' . $archive);
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
                if(copy($url, $destination.'/'.$archive, $context))
                {
                    try
                    {
                        $phar = new PharData($destination.'/'.$archive);

                        //Update the metadata
                        $metadata = $phar->getMetadata();
                        $metadata['url'] = $url;

                        $phar->setMetadata($metadata);

                        if(isset($metadata['version'])) {
                            error_log(sprintf('%s - Update Success: %s, version %s'."\n", $date, $url, $metadata['version']), 3, $log);
                        } else {
                            error_log(sprintf('%s - Update Success: %s'."\n", $date, $url), 3, $log);
                        }

                        $result =  $destination.'/'.$archive;
                    }
                    catch(Exception $e) {
                        error_log(sprintf('%s - Update Failed: %s, error %s'."\n", $date, $url, $e->getMessage()), 3, $log);
                    }
                }
                else error_log(sprintf('%s - Update Failed: %s, could not copy from url'."\n", $date, $url), 3, $log);
            }
        }

        return $result;
    }

    protected function _archiveExtension($path, $destination)
    {
        $file = $path.'/manifest.yaml';

        if($this->_canArchive($destination) && file_exists($file))
        {
            if($this->getConfig()->log_path) {
                $log  = $this->getConfig()->log_path.'/extension.log';
            } else {
                $log  = $this->getObject('com:pages.config')->getLogPath().'/extension.log';
            }

            $date = date('y:m:d h:i:s');

            $size = function ($directory) use (&$size)
            {
                $result = array();

                if (is_dir($directory))
                {
                    $files = array_diff(scandir($directory), array('.', '..', '.DS_Store'));

                    foreach ($files as $file)
                    {
                        if (is_dir($directory . '/' . $file)) {
                            $result[$file] = $size($directory . '/' . $file);
                        } else {
                            $result[$file] = sprintf('%u', filesize($directory . '/' . $file));
                        }
                    }
                }
                else $result[basename($directory)] = sprintf('%u', filesize($directory));

                return $result;
            };

            //Load the manifest
            $manifest = $this->getObject('object.config.factory')->fromFile($file, false);

            $build   = $manifest['build']  ?? 0;
            $version = $manifest['version'] ?? 'unknown';

            $archive = 'create';
            $hash    = hash('crc32b', serialize($size($path)));
            $name    = basename(dirname($file));

            if (file_exists($destination . '/' . $name . '.zip'))
            {
                $phar = new PharData($destination . '/' . $name . '.zip');
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
                    $phar = new PharData($destination . '/' . $name . '.zip');
                    $phar->buildFromDirectory($path );
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

    protected function _canInstall($destination)
    {
        if($destination !== false)
        {
            if(!is_dir($destination)) {
                throw new RuntimeException("Cannot install extensions. Path does not exist: $destination");
            }

            if($this->getObject('com:pages.config')->debug)
            {
                if(!class_exists('PharData')) {
                    throw new RuntimeException('Cannot install extensions. Phar extension not available');
                }
            }

        }

        return $destination && class_exists('PharData');
    }

    protected function _canArchive($destination)
    {
        if($destination !== false)
        {
            if(!is_dir($destination)) {
                throw new RuntimeException("Cannot archive extensions. Path does not exist: $destination");
            }

            if($this->getObject('com:pages.config')->debug)
            {
                if(!class_exists('PharData')) {
                    throw new RuntimeException('Cannot archive extensions. Phar extension not available');
                }
            }
        }

        return $destination && class_exists('PharData');
    }
}