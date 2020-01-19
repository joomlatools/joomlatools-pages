<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

final class ComPagesDataRegistry extends KObject implements KObjectSingleton
{
    private $__data    = array();
    private $__locator = null;
    private $__namespaces = array();
    private $__cache_keys  = array();

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the locator
        $this->__locator = $this->getObject('com://site/pages.data.locator');

        //Set the namespaces
        $this->__namespaces = KObjectConfig::unbox($config->namespaces);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'cache'      => JDEBUG ? false : true,
            'cache_path' => $this->getObject('com://site/pages.config')->getSitePath('cache'),
            'cache_validation'  => true,
            'namespaces' => array(),
        ]);

        parent::_initialize($config);
    }

    public function getLocator()
    {
        return $this->__locator;
    }

    public function getNamespaces()
    {
        return $this->__namespaces;
    }

    public function fromUrl($url)
    {
        if (!isset($this->__data[$url]))
        {
            $data = $this->getObject('com://site/pages.http.client')->get($url);

            $class = $this->getObject('manager')->getClass('com://site/pages.data.object');
            $data = new $class($data);

            $this->__data[$url] = $data;
        }
        else $data = $this->__data[$url];

        return $data;
    }

    public function fromPath($path)
    {
        if(!isset($this->__data[$path]))
        {
            //Find path and base_path
            if ($namespace = parse_url($path, PHP_URL_SCHEME))
            {
                $file_path = str_replace($namespace . '://', '', $path);
                $base_path = $this->__namespaces[$namespace];
            }
            else
            {
                $file_path = $path;
                $base_path = $this->getLocator()->getConfig()->base_path;
            }

            $this->getLocator()->setBasePath($base_path);

            //Load the data and cache it
            $segments  = explode('/', $file_path);
            $root_path = array_shift($segments);
            $key = crc32($base_path . $root_path);

            if (!isset($this->__data[$key]))
            {
                $data = $this->loadCache($root_path, false);
                $this->__data[$key] = $data;
            }
            else $data = $this->__data[$key];

            //Find the specific data segment
            foreach ($segments as $segment)
            {
                if (!isset($data[$segment]))
                {
                    $data = array();
                    break;
                }
                else $data = $data[$segment];
            }

            $class = $this->getObject('manager')->getClass('com://site/pages.data.object');
            $data = new $class($data);

            $this->__data[$path] = $data;
        }
        else $data = $this->__data[$path];

        return $data;
    }

    private function __fromPath($path)
    {
        //Locate the data file
        if (!$file = $this->getLocator()->locate($path)) {
            throw new InvalidArgumentException(sprintf('The data path "%s" does not exist.', $path));
        }

        if(is_dir($file)) {
            $result = $this->__fromDirectory($file);
        } else {
            $result = $this->__fromFile($file);
        }

        return $result;
    }

    private function __fromFile($file)
    {
        return $this->getObject('object.config.factory')->fromFile($file, false);
    }

    private function __fromDirectory($path)
    {
        $data  = array();
        $nodes = array();

        $basepath = $this->getLocator()->getBasePath();
        $basepath = ltrim(str_replace($basepath, '', $path), '/');

        //List
        foreach (new DirectoryIterator($path) as $node)
        {
            if(strpos($node->getFilename(), '.order.') !== false) {
                $nodes = array_merge($this->__fromFile((string)$node->getFileInfo()), $nodes);
            }

            if (!in_array($node->getFilename()[0], array('.', '_'))) {
                $nodes[] = $node->getFilename();
            }
        }

        $nodes = array_unique($nodes);

        //Files
        $files = array();
        $dirs  = array();
        foreach($nodes as $node)
        {
            $info = pathinfo($node);

            if(isset($info['extension'])) {
                $files[$info['filename']] = $basepath.'/'.$node;
            } else {
                $dirs[$node] = $basepath.'/'.$node;
            }
        }

        foreach($files as $name => $file)
        {
            if($name !== basename(dirname($file))) {
                $data[$name] = $this->__fromPath($file);
            } else {
                $data = $this->__fromPath($file);
            }
        }

        foreach($dirs as $name => $dir) {
            $data[$name] = $this->__fromPath($dir);
        }

        return $data;
    }

    public function buildCache()
    {
        if($this->getConfig()->cache)
        {
            $basedir = $this->getLocator()->getBasePath();

            foreach (new DirectoryIterator($basedir) as $node)
            {
                if (!in_array($node->getFilename()[0], array('.', '_'))) {
                    $this->loadCache(pathinfo($node, PATHINFO_FILENAME), true);
                }
            }
        }

        return false;
    }

    public function loadCache($path, $refresh = true)
    {
        $file = $this->getLocator()->getBasePath().'/'.$path;

        if ($refresh || !$cache = $this->isCached($file))
        {
            $data = $this->__fromPath($path);

            $result = array();
            $result['data'] = $data;

            //Calculate the key
            if($this->getConfig()->cache && $this->getConfig()->cache_validation) {
                $result['key'] = $this->getCacheKey($path);
            }

            $this->storeCache($file, $result);
        }
        else
        {
            if (!$result = require($cache)) {
                throw new RuntimeException(sprintf('The data "%s" cannot be loaded from cache.', $cache));
            }

            //Check if the cache is still valid, if not refresh it
            if($this->getConfig()->cache_validation && $result['key'] != $this->getCacheKey($path)) {
                $this->loadCache($path, true);
            }

            $data = $result['data'];
        }

        return $data;
    }

    public function storeCache($file, $data)
    {
        if($this->getConfig()->cache)
        {
            $path = $this->getConfig()->cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0755, true) && !is_dir($path))) {
                throw new RuntimeException(sprintf('The data cache path "%s" does not exist', $path));
            }

            if(!is_writable($path)) {
                throw new RuntimeException(sprintf('The data cache path "%s" is not writable', $path));
            }

            if(!is_string($data))
            {
                $result = '<?php /*//path:'.$file.'*/'."\n";
                $result .= 'return '.var_export($data, true).';';
            }

            $hash = crc32($file.PHP_VERSION);
            $file  = $this->getConfig()->cache_path.'/data_'.$hash.'.php';

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The data cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function isCached($file)
    {
        $result = false;

        if($this->getConfig()->cache)
        {
            $hash   = crc32($file.PHP_VERSION);
            $cache  = $this->getConfig()->cache_path.'/data_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;
        }

        return $result;
    }

    public function getCacheKey($path)
    {
        $size = function($path) use(&$size)
        {
            $result = array();

            if (is_dir($path))
            {
                $files = array_diff(scandir($path), array('.', '..', '.DS_Store'));

                foreach ($files as $file)
                {
                    if (is_dir($path.'/'.$file)) {
                        $result[$file] =  $size($path .'/'.$file);
                    } else {
                        $result[$file] = sprintf('%u', filesize($path .'/'.$file));
                    }
                }
            }
            else $result[basename($path)] = sprintf('%u', filesize($path));

            return $result;
        };

        if(!isset($this->__cache_keys[$path]))
        {
            if($file = $this->getLocator()->locate($path)) {
                $this->__cache_keys[$path] =  hash('crc32b', serialize( $size($file)));
            }  else {
                $this->__cache_keys[$path] = false;
            }
        }

        return $this->__cache_keys[$path];
    }
}
