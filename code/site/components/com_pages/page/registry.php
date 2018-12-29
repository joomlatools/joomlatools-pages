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

    private $__page  = array();
    private $__pages = null;
    private $__paths = array();

    protected $_cache;
    protected $_cache_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the locator
        $this->__locator = $this->getObject('com:pages.page.locator');

        //Set the cache
        $this->_cache = $config->cache;

        if(empty($config->cache_path)) {
            $this->_cache_path = $this->getLocator()->getBasePath().'/cache';
        } else {
            $this->_cache_path = $config->cache_path;
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'cache'      => false,
            'cache_path' => '',
        ]);

        parent::_initialize($config);
    }

    public function getLocator()
    {
        return $this->__locator;
    }

    public function getPages($path = '', $mode = self::PAGES_ONLY, $depth = -1)
    {
        $group = 'pages_'.crc32($mode.$depth);

        if(!isset($this->__pages[$path.$group]))
        {
            $directory = dirname($this->getLocator()->locate('page://pages/'. $path));

            if (!$cache = $this->isCached($directory, $group))
            {
                $iterator = new RecursiveArrayIterator($this->_iteratePath($path));
                $iterator = new RecursiveIteratorIterator($iterator, $mode);

                //Set the max dept, -1 for full depth
                $iterator->setMaxDepth($depth);

                $result = array();
                foreach ($iterator as $page_path => $children)
                {
                    if(!$page = $this->getPage($page_path)) {
                        throw new RuntimeException(sprintf('The page "%s"does not exist.', $page_path));
                    }

                    $result[$page_path] = $page->toArray();
                }

                //Cache the page
                $this->_writeCache($directory, $result, $group);
            }
            else
            {
                if (!$result = require($cache)) {
                    throw new RuntimeException(sprintf('The pages "%s" cannot be loaded from cache.', $cache));
                }
            }

            $this->__pages[$path.$group] = $result;
        }

        return $this->__pages[$path.$group];
    }

    public function getPage($path)
    {
        $page = null;
        $file = false;

        $path = ltrim($path, './');

        if($path && !isset($this->__page[$path]))
        {
            $file = $this->getLocator()->locate('page://pages/'. $path);

            if($file)
            {
                if(!$cache = $this->isCached($file))
                {
                    //Load the page
                    $page = (new ComPagesPage())->fromFile($file);

                    //Set the path
                    $page->path = trim(dirname($path), '.');

                    //Set the slug
                    $page->slug = basename($path, '.html');

                    //Normalise the page data
                    $this->_normalisePage($page);

                    //Cache the page
                    $this->_writeCache($file, $page->toArray());
                }
                else
                {
                    if(!$page = require($cache)) {
                        throw new RuntimeException(sprintf('The page "%s" cannot be loaded from cache.', $cache));
                    }

                    //Create a page config object
                    $page = new ComPagesObjectConfigFrontmatter($page);
                }

                $this->__page[$path] = $page;
            }
            else $this->__page[$path] = false;
        }

        return $this->__page[$path];
    }

    public function isPage($path)
    {
        if(!isset($this->__page[$path])) {
           $result = (bool) $this->getLocator()->locate('page://pages/'. $path);
        } else {
            $result = ($this->__page[$path] === false) ? false : true;
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

    public function isAccessible($path)
    {
        $result = true;

        if($page = $this->getPage($path))
        {
            //Check groups
            if(isset($page->access->groups))
            {
                $groups = $this->getObject('com:pages.database.table.groups')
                    ->select($this->getObject('user')->getGroups(), KDatabase::FETCH_ARRAY_LIST);

                $groups = array_map('strtolower', array_column($groups, 'title'));

                if(!array_intersect($groups, KObjectConfig::unbox($page->access->groups))) {
                    $result = false;
                }
            }

            //Check roles
            if($result && isset($page->access->roles))
            {
                $roles = $this->getObject('com:pages.database.table.roles')
                    ->select($this->getObject('user')->getRoles(), KDatabase::FETCH_ARRAY_LIST);

                $roles = array_map('strtolower', array_column($roles, 'title'));

                if(!array_intersect($roles, KObjectConfig::unbox($page->access->roles))) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    public function isPublished($path)
    {
        $result = true;

        if($page = $this->getPage($path))
        {
            if($page->published) {
                $result = $page->published;
            }
        }

        return $result;
    }

    public function isCached($file, $group = 'page')
    {
        $result = false;

        if($this->_cache)
        {
            $hash   = crc32($file.PHP_VERSION);
            $cache  = $this->_cache_path.'/'.$group.'_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result && file_exists($file))
            {
                if(filemtime($cache) < filemtime($file)) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    protected function _iteratePath($path = '')
    {
        $iterate = function($path) use(&$iterate)
        {
            if(!isset($this->__paths[$path]))
            {
                $files = false;

                //Only include pages
                if($directory = $this->getLocator()->locate('page://pages/'. $path))
                {
                    $nodes = array();
                    $order = array();
                    $directory  = dirname($directory);

                    $basepath = $this->getLocator()->getBasePath().'/pages';
                    $basepath = ltrim(str_replace($basepath, '', $directory), '/');

                    //List
                    foreach (new DirectoryIterator($directory) as $node)
                    {
                        if(strpos($node->getFilename(), '.order.') !== false) {
                            $order = $this->getObject('object.config.factory')->fromFile((string)$node->getFileInfo(), false);
                        } else {
                            $nodes[] = $node->getFilename();
                        }
                    }

                    //Remove files that don't exist from ordering (to prevent loops)
                    $nodes = array_merge(array_intersect($order, $nodes), $nodes);

                    //Prevent duplicates
                    if($nodes = array_unique($nodes))
                    {
                        $files = array();

                        foreach($nodes as $node)
                        {
                            //Exclude files or folder that start with '.' or '_'
                            if (!in_array($node[0], array('.', '_')))
                            {
                                $info     = pathinfo($node);
                                $filepath = $basepath ? $basepath .'/'.$info['filename'] : $info['filename'];

                                if($info['extension'])
                                {
                                    if(strpos($node, 'index') === false) {
                                        $files[$filepath] = $filepath;
                                    }
                                }
                                else
                                {
                                    if(false !== $result = $iterate($filepath))
                                    {
                                        if($result) {
                                            $files[$filepath] = $result;
                                        } else {
                                            $files[$filepath] = $filepath;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $this->__paths[$path] = $files;
            }
            else $files = $this->__paths[$path];

            return $files;
        };

        Closure::bind($iterate, $this, get_class());
        $files = $iterate($path);

        return $files;
    }

    protected function _writeCache($file, $data, $group = 'page')
    {
        if($this->_cache)
        {
            $path = $this->_cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0755, true) && !is_dir($path))) {
                throw new RuntimeException(sprintf('The page cache path "%s" does not exist', $path));
            }

            if(!is_writable($path)) {
                throw new RuntimeException(sprintf('The page cache path "%s" is not writable', $path));
            }

            $hash = crc32($file.PHP_VERSION);
            $file  = $this->_cache_path.'/'.$group.'_'.$hash.'.php';

            if(!is_string($data)) {
                $data = '<?php return '.var_export($data, true).';';
            }

            if(@file_put_contents($file, $data) === false) {
                throw new RuntimeException(sprintf('The page cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    protected function _normalisePage($page)
    {
        //Set the process
        if(!$page->process) {
            $page->process = array();
        }

        //Set the published state (if not set yet)
        if (!isset($page->published)) {
            $page->published = true;
        }

        //Set the date (if not set yet)
        if (!isset($page->date)) {
            $page->date = filemtime($page->getFilename());
        }

        //Set page default properties from collection
        if($collection = $this->getObject('page.registry')->isCollection($page->path))
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
    }
}