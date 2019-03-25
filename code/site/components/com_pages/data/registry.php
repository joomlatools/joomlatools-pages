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

    protected $_cache;
    protected $_cache_path;
    protected $_cache_time;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the locator
        $this->__locator = $this->getObject('com:pages.data.locator');

        //Set the cache
        $this->_cache = $config->cache;

        //Set the cache time
        $this->_cache_time = $config->cache_time;

        if(empty($config->cache_path)) {
            $this->_cache_path = Koowa::getInstance()->getRootPath().'/joomlatools-pages/cache';
        } else {
            $this->_cache_path = $config->cache_path;
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'cache'      => JDEBUG ? false : true,
            'cache_path' => '',
            'cache_time' => 60*60*24 //1 day
        ]);

        parent::_initialize($config);
    }

    public function getLocator()
    {
        return $this->__locator;
    }

    public function getData($path, $object = true)
    {
        $result = array();
        if(!isset($this->__data[$path]))
        {
            $segments = explode('/', $path);
            $root     = array_shift($segments);

            //Load the cache and do not refresh it
            $data = $this->loadCache($root, false);

            foreach($segments as $segment)
            {
                if(!isset($data[$segment]))
                {
                    $data = array();
                    break;
                }
                else $data = $data[$segment];
            }

            //Create the data object
            $this->__data[$path] = $data;
        }

        if($object)
        {
            $class = $this->getObject('manager')->getClass('com:pages.data.object');
            $result = new $class($this->__data[$path]);
        }
        else $result = $this->__data[$path];


        return $result;
    }

    private function __fromPath($path)
    {
        if(!parse_url($path, PHP_URL_SCHEME) == 'http')
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
        }
        else $result =  $this->getObject('com:pages.data.client')->fromUrl($path, false);

        return $result;
    }

    private function __fromFile($file)
    {
        //Get the data
        $result = array();

        $url = trim(fgets(fopen($file, 'r')));
        if(strpos($url, '://') === 0) {
            $result =  $this->getObject('com:pages.data.client')->fromUrl($url, false);
        } else {
            $result = $this->getObject('object.config.factory')->fromFile($file, false);
        }

        return $result;
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
        if($this->_cache)
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

    public function loadCache($basedir, $refresh = true)
    {
        $file = $this->getLocator()->getBasePath().'/'.$basedir;

        if (!$cache = $this->isCached($file))
        {
            $data = $this->__fromPath($basedir);
            $this->storeCache($file, KObjectConfig::unbox($data));
        }
        else
        {
            if (!$data = require($cache)) {
                throw new RuntimeException(sprintf('The data "%s" cannot be loaded from cache.', $cache));
            }
        }

        return $data;
    }

    public function storeCache($file, $data)
    {
        if($this->_cache)
        {
            $path = $this->_cache_path;

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
            $file  = $this->_cache_path.'/data_'.$hash.'.php';

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

        if($this->_cache)
        {
            $hash   = crc32($file.PHP_VERSION);
            $cache  = $this->_cache_path.'/data_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result && file_exists($file))
            {
                //Refresh cache if the file has changed or if the cache expired
                if((filemtime($cache) < filemtime($file)) || ((time() - filemtime($cache)) > $this->_cache_time)) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}