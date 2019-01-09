<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesPageRegistry extends KObject implements KObjectSingleton
{
    const PAGES_ONLY = \RecursiveIteratorIterator::LEAVES_ONLY;
    const PAGES_TREE = \RecursiveIteratorIterator::SELF_FIRST;

    private  $__locator = null;

    private $__pages  = array();
    private $__data   = null;

    protected $_cache;
    protected $_cache_path;
    protected $_cache_time;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the locator
        $this->__locator = $this->getObject('com:pages.page.locator');

        //Set the cache
        $this->_cache = $config->cache;

        //Set the cache time
        $this->_cache_time = $config->cache_time;

        if(empty($config->cache_path)) {
            $this->_cache_path =  Koowa::getInstance()->getRootPath().'/joomlatools-pages/cache';
        } else {
            $this->_cache_path = $config->cache_path;
        }

        //Load the cache and do not refresh it
        $basedir = $this->getLocator()->getBasePath().'/pages';
        $this->__data = $this->loadCache($basedir, false);
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

    public function getPages($path = '', $mode = self::PAGES_ONLY, $depth = -1)
    {
        $result = array();
        $files  = $this->__data['files'];

        if($path = trim($path, '.'))
        {
            $segments = array();
            foreach(explode('/', $path) as $segment)
            {
                $segments[] = $segment;
                if(!isset($files[implode('/', $segments)]))
                {
                    $files = false;
                    break;
                }
                else $files = $files[implode('/', $segments)];
            }
        }

        if($files)
        {
            $iterator = new RecursiveArrayIterator($files);
            $iterator = new RecursiveIteratorIterator($iterator, $mode);

            //Set the max dept, -1 for full depth
            $iterator->setMaxDepth($depth);

            foreach ($iterator as $page => $file)
            {
                if(!is_string($file))
                {
                    if(!$file = $this->getLocator()->locate('page://pages/'. $page)) {
                        throw new RuntimeException(sprintf('The page "%s"does not exist.', $page));
                    }
                }

                //Get the relative file path
                $basedir = $this->getLocator()->getBasePath().'/pages';
                $file    = trim(str_replace($basedir, '', $file), '/');

                $result[$page] = $this->__data['pages'][$file];
            }
        }

        return $result;
    }

    public function getPage($path)
    {
        $page = false;

        $path = ltrim($path, './');

        if($path && !isset($this->__pages[$path]))
        {
            if($file = $this->getLocator()->locate('page://pages/'. $path))
            {
                //Get the relative file path
                $basedir = $this->getLocator()->getBasePath().'/pages';
                $file    = trim(str_replace($basedir, '', $file), '/');

                //Load the page
                $page = new ComPagesPage($this->__data['pages'][$file]);

                //Set page default properties from collection
                if($collection = $this->isCollection($page->path))
                {
                    if(isset($collection['page']))
                    {
                        foreach($collection['page'] as $property => $value)
                        {
                            if(!$page->has($property)) {
                                $page->set($property, $value);
                            }
                        }
                    }
                }

                //Set the layout (if not set yet)
                if($page->has('layout') && is_string($page->layout)) {
                    $page->layout = array('path' => $page->layout);
                }

                $this->__pages[$path] = $page;
            }
            else $this->__pages[$path] = false;
        }

        if (isset($this->__pages[$path])) {
            $page = $this->__pages[$path];
        }

        return $page;
    }

    public function isPage($path)
    {
        if(!isset($this->__pages[$path])) {
           $result = (bool) $this->getLocator()->locate('page://pages/'. $path);
        } else {
            $result = ($this->__pages[$path] === false) ? false : true;
        }

        return $result;
    }

    public function isCollection($path)
    {
        $result = false;
        if($page  = $this->getPage($path)) {
            $result = isset($page->collection) && $page->collection !== false ? KObjectConfig::unbox($page->collection) : false;
        }

        return $result;
    }

    public function buildCache()
    {
        if($this->_cache)
        {
            $basedir = $this->getLocator()->getBasePath().'/pages';
            $this->loadCache($basedir, true);
        }

        return false;
    }

    public function loadCache($basedir, $refresh = true)
    {
        if ($refresh || (!$cache = $this->isCached($basedir)))
        {
            $data = array();

            //Create the data
            $iterate = function ($dir) use (&$iterate, $basedir, &$data)
            {
                $nodes = array();
                $order = array();
                $files = array();

                //Only include pages
                if(is_dir($dir) && !empty(glob($dir.'/index*')))
                {
                    //List
                    foreach (new DirectoryIterator($dir) as $node)
                    {
                        if (strpos($node->getFilename(), '.order.') !== false) {
                            $order = $this->getObject('object.config.factory')->fromFile((string)$node->getFileInfo(), false);
                        } else {
                            $nodes[] = $node->getFilename();
                        }
                    }

                    //Remove files that don't exist from ordering (to prevent loops)
                    $nodes = array_merge(array_intersect($order, $nodes), $nodes);

                    //Prevent duplicates
                    if ($nodes = array_unique($nodes))
                    {
                        foreach ($nodes as $node)
                        {
                            //Exclude files or folder that start with '.' or '_'
                            if (!in_array($node[0], array('.', '_')))
                            {
                                $info = pathinfo($node);

                                $file = $dir . '/' . $node;

                                if (strpos($node, 'index') !== false) {
                                    $path = trim(str_replace($basedir, '', $dir), '/');
                                } else {
                                    $path = trim(str_replace($basedir, '', $dir . '/' . $info['filename']), '/');
                                }

                                if (isset($info['extension']))
                                {
                                    //Load the page
                                    $page = (new ComPagesPage())->fromFile($file);

                                    //Set the path
                                    $page->path = trim(dirname($path), '.');

                                    //Set the slug
                                    $page->slug = basename($path, '.html');

                                    //Set the process
                                    if (!$page->process) {
                                        $page->process = array();
                                    }

                                    //Set the published state (if not set yet)
                                    if (!isset($page->published)) {
                                        $page->published = true;
                                    }

                                    //Set the date (if not set yet)
                                    if (!isset($page->date)) {
                                        $page->date = filemtime($file);
                                    }

                                    //Handle dynamic data
                                    array_walk_recursive ($page, function(&$value, $key)
                                    {
                                        if(is_string($value) && strpos($value, 'data://') === 0)
                                        {
                                            $matches = array();
                                            preg_match('#data\:\/\/([^\[]+)(?:\[(.*)\])*#si', $value, $matches);

                                            if(!empty($matches[0]))
                                            {
                                                $data = $this->getObject('data.registry')->getData($matches[1]);

                                                if($data && !empty($matches[2])) {
                                                    $data = $data->get($matches[2]);
                                                }

                                                $value = $data;
                                            }
                                        }
                                    });

                                    //Store the relative file path
                                    $file = trim(str_replace($basedir, '', $file), '/');

                                    $data[$file] = $page->toArray();

                                    if (strpos($node, 'index') === false) {
                                        $files[$path] = $file;
                                    }
                                }
                                else
                                {
                                    if($result = $iterate($file)) {
                                        $files[$path] = $result;
                                    }
                                }
                            }
                        }
                    }

                    return $files;
                }
                else return false;
            };

            Closure::bind($iterate, $this, get_class());

            $result['files'] = $iterate($basedir);
            $result['pages'] = $data;

            $this->storeCache($basedir, $result);
        }
        else
        {
            if (!$result = require($cache)) {
                throw new RuntimeException(sprintf('The page registry "%s" cannot be loaded from cache.', $cache));
            }
        }

        return $result;

    }

    public function storeCache($file, $data)
    {
        if($this->_cache)
        {
            $path = $this->_cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0755, true) && !is_dir($path))) {
                throw new RuntimeException(sprintf('The page registry cache path "%s" does not exist', $path));
            }

            if(!is_writable($path)) {
                throw new RuntimeException(sprintf('The page registry cache path "%s" is not writable', $path));
            }

            if(!is_string($data))
            {
                $result = '<?php /*//path:'.$file.'*/'."\n";
                $result .= 'return '.var_export($data, true).';';
            }

            $hash = crc32($file.PHP_VERSION);
            $file  = $this->_cache_path.'/page_'.$hash.'.php';

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The page registry cannot be cached in "%s"', $file));
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
            $cache  = $this->_cache_path.'/page_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result && file_exists($file))
            {
                if((filemtime($cache) < filemtime($file)) || ((time() - filemtime($cache)) > $this->_cache_time)) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}