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
    protected $_pages       = array();
    protected $_collection  = array();

    protected $_base_path;

    protected $_cache;
    protected $_cache_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_base_path  = rtrim($config->base_path, '/');
        $this->_cache      = $config->cache;

        if(empty($config->cache_path)) {
            $this->_cache_path = $this->getObject('com:pages.page.locator')->getBasePath().'/cache';
        } else {
            $this->_cache_path = $config->cache_path;
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'base_path'  => 'page://pages',
            'cache'      => true,
            'cache_path' => '',
        ]);

        parent::_initialize($config);
    }

    public function getPage($path)
    {
        $page = null;

        if(!isset($this->_pages[$path]))
        {
            $url = $this->_base_path . '/' . $path;

            if($file = $this->getObject('template.locator.factory')->locate($url))
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
                        throw new RuntimeException(sprintf('The page data "%s" cannot be loaded from cache.', $cache));
                    }

                    //Create a page config object
                    $page = new ComPagesObjectConfigFrontmatter($page);
                }

                $this->_pages[$path] = $page;
            }
            else $this->_pages[$path] = false;
        }

        return $this->_pages[$path];
    }

    public function isPage($path)
    {
        if(!isset($this->_pages[$path]))
        {
            $url = $this->_base_path . '/' . $path;
            $result = $this->getObject('template.locator.factory')->locate($url);
        }
        else $result = ($this->_pages[$path] === false) ? false : true;

        return $result;
    }

    public function getCollection($path)
    {
        if(!isset($this->_collection[$path]))
        {
            if($this->isCollection($path))
            {
                $pages = array();

                $page = $this->getPage($path);

                if(isset($page->collection->root))
                {
                    $path = $page->collection->root;
                    $directory = $this->getObject('template.locator.factory')
                        ->locate($this->_base_path.'/'.$path);
                }
                else $directory = $page->filename;

                if(!$cache = $this->isCached(dirname($directory), 'collection'))
                {
                    $files = glob(dirname($directory).'/*.*');
                    foreach($files as $file)
                    {
                        $slug = pathinfo($file, PATHINFO_FILENAME);

                        if ($slug != 'index')
                        {
                            $page = $this->getPage($path . '/' . $slug)->toArray();
                            unset($page['content']);

                            $pages[] = $page;
                        }
                    }
                    //Cache the collection
                    $this->_writeCache(dirname($directory), $pages, 'collection');
                }
                else
                {
                    if(!$pages = require($cache)) {
                        throw new RuntimeException(sprintf('The collection "%s" cannot be loaded from cache.', $cache));
                    }
                }

                $this->_collection[$path] = $pages;
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