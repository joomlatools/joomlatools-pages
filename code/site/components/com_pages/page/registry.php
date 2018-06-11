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
    protected $_pages  = array();
    protected $_data   = array();
    protected $_content = array();

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
        $config->append(array(
            'base_path'  => 'page://pages',
            'cache'      => true,
            'cache_path' => '',
        ));

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
                //Load the page
                $page = (new ComPagesPage())->fromFile($file);

                //Set the path
                $page->path = $path;

                //Set the slug
                $page->slug = basename($path);

                //Set the published state (if not set yet)
                if(!isset($page->published)) {
                    $page->published = true;
                }

                //Set the date (if not set yet)
                if(!isset($page->date)) {
                    $page->date = filemtime($page->getFilename());
                }
            }
            else $page = false;

            $this->_pages[$path] = $page;
        }

        return $this->_pages[$path];
    }

    public function hasPage($path)
    {
        if(!isset($this->_pages[$path]))
        {
            $url = $this->_base_path . '/' . $path;
            $result = $this->getObject('template.locator.factory')->locate($url);
        }
        else $result = ($this->_pages[$path] === false) ? false : true;

        return $result;
    }

    public function getPageContent($path)
    {
        if(!$this->_content[$path])
        {
            $page = $this->getPage($path);

            if($page && !$page->isCollection())
            {
                $file = $page->getFilename();

                if(!$cache = $this->isCached($file))
                {
                    $url     = $this->_base_path.'/'.$path;
                    $type    = $page->getFiletype();
                    $content = $page->getContent();

                    $content = $this->getObject('com:pages.template.page')
                        ->loadString($content, $type, $url)
                        ->render($page->toArray());

                    //Cache the page
                    $this->_writeCache($file, $content);
                }
                else
                {
                    if(!$content = file_get_contents($cache)) {
                        throw new RuntimeException(sprintf('The page "%s" cannot be loaded from cache.', $cache));
                    }
                }

                $this->_content[$path] = $content;
            }
            else $this->_content[$path] = false;
        }

        return $this->_content[$path];
    }

    public function getCollection($path)
    {
        if(!isset($this->_data[$path]))
        {
            $page = $this->getPage($path);

            if($page->isCollection())
            {
                $pages = array();

                if($root = $page->collection['root'])
                {
                    $path = $root;
                    $directory = $this->getObject('template.locator.factory')
                        ->locate($this->_base_path.'/'.$path);
                }
                else $directory = $page->getFilename();

                if(!$cache = $this->isCached(dirname($directory)))
                {
                    $iterator = new FilesystemIterator(dirname($directory));
                    while ($iterator->valid()) {
                        $slug = pathinfo($iterator->current()->getRealpath(), PATHINFO_FILENAME);

                        if ($slug != 'index') {
                            $pages[] = $this->getPage($path . '/' . $slug)->toArray();
                        }

                        $iterator->next();
                    }

                    //Cache the data
                    $this->_writeCache(dirname($directory), $pages);
                }
                else
                {
                    if(!$pages = require($cache)) {
                        throw new RuntimeException(sprintf('The data "%s" cannot be loaded from cache.', $cache));
                    }
                }

                $this->_data[$path] = $pages;
            }
        }

        return isset($this->_data[$path]) ? $this->_data[$path] : null;
    }

    public function isCollection($path)
    {
        $result = false;
        if($page  = $this->getPage($path)) {
            $result = $page->isCollection();
        }

        return $result;
    }

    public function isAccessible($path)
    {
        $result = true;

        if($page = $this->getPage($path))
        {
            //Check groups
            if($page->access->groups)
            {
                $groups = $this->getObject('com:pages.database.table.groups')
                    ->select($this->getObject('user')->getGroups(), KDatabase::FETCH_ARRAY_LIST);

                $groups = array_map('strtolower', array_column($groups, 'title'));

                if(!array_intersect($groups, $page->access->groups->toArray())) {
                    $result = false;
                }
            }

            //Check roles
            if($result && $page->access->roles)
            {
                $roles = $this->getObject('com:pages.database.table.roles')
                    ->select($this->getObject('user')->getRoles(), KDatabase::FETCH_ARRAY_LIST);

                $roles = array_map('strtolower', array_column($roles, 'title'));

                if(!array_intersect($roles, $page->access->roles->toArray())) {
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

    public function isCached($file)
    {
        $result = false;

        if($this->_cache)
        {
            $hash   = crc32($file.PHP_VERSION);
            $cache  = $this->_cache_path.'/'.$hash.'.php';
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

    protected function _writeCache($file, $data)
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
            $file = $path.'/'.$hash.'.php';

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