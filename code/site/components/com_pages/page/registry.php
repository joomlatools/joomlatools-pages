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

    private $__page  = array();
    private $__pages = null;

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
            'cache_path' => '',
        ]);

        parent::_initialize($config);
    }

    public function getPages($path = '', $mode = self::PAGES_ONLY, $depth = -1)
    {
        $group = 'pages_'.crc32($mode.$depth);

        if(!isset($this->__pages[$path.$group]))
        {
            $directory = dirname($this->getObject('com:pages.page.locator')->locate('page://pages/'. $path));

            if (!$cache = $this->isCached($directory, $group))
            {
                $iterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
                $iterator = new ComPagesRecursiveFilterIterator($iterator);
                $iterator = new RecursiveIteratorIterator($iterator, $mode);

                //Set the max dept, -1 for full depth
                $iterator->setMaxDepth($depth);

                $result = array();
                foreach ($iterator as $file)
                {
                    $page_path = trim(dirname($iterator->getSubpathname()), '.');
                    $page_file = pathinfo($file, PATHINFO_FILENAME);

                    $page_path = $page_path ? $page_path. '/' . $page_file : $page_file;

                    //Pre-pend the path
                    if(!empty($path)) {
                        $page_path = $path.'/'.$page_path;
                    }

                    $page = $this->getPage($page_path)->toArray();
                    unset($page['content']);

                    $result[$page_path] = $page;
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
            $file = $this->getObject('com:pages.page.locator')->locate('page://pages/'. $path);

            if($file)
            {
                if(!$cache = $this->isCached($file))
                {
                    //Load the page
                    $page = (new ComPagesPage())->fromFile($file);

                    //Process
                    $this->_processData($page);

                    //Set the path
                    $page->path = trim(dirname($path), '.');

                    //Set the slug
                    $page->slug = basename($path);

                    //Set the process
                    $page->process = array();

                    //Set the published state (if not set yet)
                    if (!isset($page->published)) {
                        $page->published = true;
                    }

                    //Set the date (if not set yet)
                    if (!isset($page->date)) {
                        $page->date = filemtime($page->getFilename());
                    }

                    if(!isset($page->collection) || $page->collection == false)
                    {
                        //Render the content
                        $type    = $page->getFiletype();
                        $content = $page->getContent();

                        $page->content = $this->getObject('com:pages.template.page')
                            ->loadString($content, $type, 'page://pages/'. $path)
                            ->render($page->toArray());
                    }
                    else $page->content = '';

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
           $result = (bool) $this->getObject('com:pages.page.locator')->locate('page://pages/'. $path);
        } else {
            $result = ($this->__page[$path] === false) ? false : true;
        }

        return $result;
    }

    public function isPageFormat($path, $format)
    {
        $result = false;
        if($this->isPage($path))
        {
            $formats = $this->getObject('com:pages.page.locator')->formats('page://pages/'. $path);
            if(isset($formats[$format])) {
                $result = true;
            }
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

    protected function _processData($page)
    {
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

class ComPagesRecursiveFilterIterator extends RecursiveFilterIterator
{
    protected $_basepath;

    public function __construct(RecursiveDirectoryIterator $iterator)
    {
        $this->_basepath =  KObjectManager::getInstance()->getObject('com:pages.page.locator')->getBasePath().'/pages';

        parent::__construct($iterator);
    }

    public function accept()
    {
        $result  = false;
        $current = $this->getBasename();

        if(strlen($current) && !in_array($current[0], array('.', '_')))
        {
            if($this->getPath() === $this->_basepath || strpos($current, 'index') === false) {
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