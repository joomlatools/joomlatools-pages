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
    protected $_page        = array();
    protected $_collection  = array();
    protected $_pages       = null;

    protected $_cache;
    protected $_cache_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_cache = $config->cache;

        if(empty($config->cache_path)) {
            $this->_cache_path = $this->getObject('com:pages.page.locator')->getBasePath().'/cache';
        } else {
            $this->_cache_path = $config->cache_path;
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'cache'      => false,
            'cache'      => false,
            'cache_path' => '',
        ]);

        parent::_initialize($config);
    }

    public function getPages()
    {
        if(!isset($this->_pages))
        {
            $directory = $this->getObject('com:pages.page.locator')->getBasePath().'/pages';

            if (!$cache = $this->isCached($directory, 'pages'))
            {
                $iterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
                $iterator = new ComPagesRecursiveFilterIterator($iterator);
                $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

                $result = array();
                foreach ($iterator as $file)
                {
                    $path = trim(dirname($iterator->getSubpathname()), '.');
                    $slug = pathinfo($file, PATHINFO_FILENAME);

                    if($path) {
                        $page = $path . '/' . $slug;
                    } else {
                        $page = $slug;
                    }

                    $page = $this->getPage($page)->toArray();

                    //Set the parent path
                    $page['parent_path'] = $path;

                    unset($page['content']);

                    $result[] = $page;
                }

                //Cache the page
                $this->_writeCache($directory, $result, 'pages');
            }
            else
            {
                if (!$result = require($cache)) {
                    throw new RuntimeException(sprintf('The pages "%s" cannot be loaded from cache.', $cache));
                }
            }

            $this->_pages = $result;
        }

        return $this->_pages;
    }

    public function getPage($path)
    {
        $page = null;
        $file = false;

        if($path && !isset($this->_page[$path]))
        {
            $locations = array('page://pages', 'page://collections');
            foreach($locations as $location)
            {
                $url = $location. '/'. $path;
                if($file = $this->getObject('template.locator.factory')->locate($url)) {
                    break;
                }
            }

            if($file)
            {
                if(!$cache = $this->isCached($file, 'page'))
                {
                    //Load the page
                    $page = (new ComPagesPage())->fromFile($file);

                    //Set the path
                    $page->path = $path;

                    //Set the slug
                    $page->slug = basename($path);

                    //Set the published state (if not set yet)
                    if (!isset($page->published)) {
                        $page->published = true;
                    }

                    //Set the date (if not set yet)
                    if (!isset($page->date)) {
                        $page->date = filemtime($page->filename);
                    }

                    if(!isset($page->collection) || $page->collection == false)
                    {
                        //Render the content
                        $type    = $page->getFiletype();
                        $content = $page->getContent();

                        $page->content = $this->getObject('com:pages.template.page')
                            ->loadString($content, $type, $url)
                            ->render($page->toArray());
                    }
                    else $page->content = '';

                    //Cache the page
                    $this->_writeCache($file, $page->toArray(), 'page');
                }
                else
                {
                    if(!$page = require($cache)) {
                        throw new RuntimeException(sprintf('The page "%s" cannot be loaded from cache.', $cache));
                    }

                    //Create a page config object
                    $page = new ComPagesObjectConfigFrontmatter($page);
                }

                $this->_page[$path] = $page;
            }
            else $this->_page[$path] = false;
        }

        return $this->_page[$path];
    }

    public function isPage($path)
    {
        if(!isset($this->_page[$path]))
        {
            $result    = false;
            $locations = array('page://pages', 'page://collections');
            foreach($locations as $location)
            {
                $url = $location. '/'. $path;
                if($result = $this->getObject('template.locator.factory')->locate($url)) {
                    break;
                }
            }
        }
        else $result = ($this->_page[$path] === false) ? false : true;

        return $result;
    }

    public function getCollection($path)
    {
        if(!isset($this->_collection[$path]))
        {
            if($this->isCollection($path))
            {
                $collection = array();
                $page  = $this->getPage($path);

                if(isset($page->collection->path)) {
                    $path = $page->collection->path;
                } else {
                    $path = $page->path;
                }

                if(isset($page->collection->layout)) {
                    $layout = $page->collection->layout;
                }

                $directory = $this->getObject('com:pages.page.locator')->getBasePath().'/collections/'.$path;
                if(!is_dir($directory)) {
                    throw new RuntimeException(sprintf('The collection "%s" does not exist', 'collections/'.$path));
                }

                if(!$cache = $this->isCached($directory, 'collection'))
                {
                    $files = glob($directory.'/*.*');
                    foreach($files as $file)
                    {
                        $slug = pathinfo($file, PATHINFO_FILENAME);
                        $page = $this->getPage($path . '/' . $slug)->toArray();

                        //Set the layout for the page
                        if(!isset($page['layout']) && $layout) {
                            $page['layout'] = $layout;
                        }

                        unset($page['content']);

                        $collection[] = $page;
                    }

                    //Cache the collection
                    $this->_writeCache($directory, $collection, 'collection');
                }
                else
                {
                    if(!$collection = require($cache)) {
                        throw new RuntimeException(sprintf('The collection "%s" cannot be loaded from cache.', $cache));
                    }
                }

                $this->_collection[$path] = $collection;
            }
        }

        return isset($this->_collection[$path]) ? $this->_collection[$path] : null;
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

    public function isCached($file, $group = 'cache')
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

    protected function _writeCache($file, $data, $group = 'cache')
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
}

class ComPagesRecursiveFilterIterator extends RecursiveFilterIterator
{
    public function __construct(RecursiveDirectoryIterator $iterator)
    {
        parent::__construct($iterator);
    }

    public function accept()
    {
        $result  = false;
        $current = $this->getBasename();

        if(strlen($current) && !in_array($current[0], array('.', '_')))
        {
            if(strpos($current, 'index') === false) {
                $result = true;
            }
        }

        return $result;
    }

    public function hasChildren()
    {
        return parent::hasChildren() && $this->accept();
    }
}