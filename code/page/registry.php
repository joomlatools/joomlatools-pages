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
    const PAGES_TREE = \RecursiveIteratorIterator::SELF_FIRST;
    const PAGES_LIST = \RecursiveIteratorIterator::CHILD_FIRST;
    const PAGES_ONLY = \RecursiveIteratorIterator::LEAVES_ONLY;

    private $__locator = null;

    private $__pages  = array();
    private $__data   = null;
    private $__collections = array();
    private $__redirects   = array();
    private $__hashes      = array();

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the locator
        $this->__locator = $this->getObject('com:pages.page.locator');

        //Load the cache and do not refresh it
        $basedir = $this->getLocator()->getBasePath();
        $this->__data = $this->loadCache($basedir, false);

        //Set the collection
        $this->__collections = array_merge(KObjectConfig::unbox($config->collections), $this->__data['collections']);

        //Set the redirects
        $this->__redirects = array_merge(KObjectConfig::unbox($config->redirects), $this->__data['redirects']);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'cache'         => $this->getObject('pages.config')->debug ? false : true,
            'cache_path'    => $this->getObject('pages.config')->getCachePath(),
            'cache_validation' => true,
            'collections' => array(
                'pages' => ['model' => 'com:pages.model.pages'],
                'cache' => [
                    'model' => 'com:pages.model.cache',
                    'state' => ['limit' => 100]
                ]
            ),
            'redirects'   => array(),
            'properties'  => array(),
            'types'       => ['form', 'collection', 'redirect']
        ]);

        parent::_initialize($config);
    }

    public function getHash($path = null)
    {
        $result = null;

        if($path)
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
                            $result[$file] = sprintf('%u', filemtime($path .'/'.$file));
                        }
                    }
                }
                else $result[basename($path)] = sprintf('%u', filemtime($path));

                return $result;
            };

            if(file_exists($path))
            {
                if(!isset($this->__hashes[$path])) {
                    $this->__hashes[$path] =  hash('crc32b', serialize($size($path)));
                }

                $result = $this->__hashes[$path];
            }
        }
        else $result = $this->__data['hash'];

        return $result;
    }

    public function getLocator()
    {
        return $this->__locator;
    }

    public function getCollection($name)
    {
        $result = false;

        if(isset($this->__collections[$name]))
        {
            $result = new ComPagesObjectConfig($this->__collections[$name]);

            //If the collections extends another collection merge it
            if(isset($result->extend))
            {
                if(!$extend = $this->getCollection($result->extend))
                {
                    throw new RuntimeException(
                        sprintf('Cannot extend from collection. No collection defined in: %s', $result->extend)
                    );
                }

                //Merge page
                if($extend->has('config')) {
                    $extend->config->merge($result->get('config', array()));
                } else {
                    $extend->config = $result->get('config');
                }

                //Merge state
                if($extend->has('state')) {
                    $extend->state->merge($result->get('state', array()));
                } else {
                    $extend->state = $result->get('state');
                }

                //Merge state
                $filter = (array) KObjectConfig::unbox($result->get('filter', []));

                if($extend->has('filter')) {
                    $extend->filter->merge($filter);
                } else {
                    $extend->filter = $filter;
                }

                //Merge page
                if($extend->has('page')) {
                    $extend->page->merge($result->get('page', array()));
                } else {
                    $extend->page = $result->get('page');
                }

                //Merge type
                if($result->has('type')) {
                    $extend->type = $result->get('type');
                }

                //Merge route
                if($result->has('route')) {
                    $extend->route = $result->get('route');
                }

                //Merge format
                if($result->has('format')) {
                    $extend->format = $result->get('format');
                }

                $result = $extend;
            }

            if(!isset($result->model)) {
                $result->model = 'com:pages.model.pages';
            }
        }
        else
        {
            //Assume we are being passed a fully qualified identifier
            if(is_string($name) && strpos($name, ':') !== false) {
                $result = new ComPagesObjectConfig(['model' => $name]);
            }
        }

        return $result;
    }

    public function getRedirects()
    {
        return $this->__redirects;
    }

    public function getPages($path = '/', $mode = self::PAGES_LIST, $depth = -1)
    {
        $result = array();


        //Get a list of pages
        if($mode != self::PAGES_ONLY)
        {
            $files  = $this->__data['files'];

            if($path = ltrim($path, '/'))
            {
                $segments = array();
                foreach(explode('/', $path) as $segment)
                {
                    $segments[] = $segment;
                    if(!isset($files['/'.implode('/', $segments)]))
                    {
                        $files = false;
                        break;
                    }
                    else $files = $files['/'.implode('/', $segments)];
                }
            }

            if(is_array($files))
            {
                $iterator = new RecursiveArrayIterator($files);
                $iterator = new RecursiveIteratorIterator($iterator, $mode);

                //Set the max dept, -1 for full depth
                $iterator->setMaxDepth($depth);

                foreach ($iterator as $page => $file)
                {
                    if(!is_string($file))
                    {
                        //Do not include a directory without an index file (no existing page)
                        if(!$file = $this->getLocator()->locate('page:'. $page)) {
                            continue;
                        }
                    }

                    //Get the relative file path
                    $basedir = $this->getLocator()->getBasePath();
                    $file    = trim(str_replace($basedir, '', $file), '/');

                    if(isset($this->__data['routes'][$page])) {
                        $result[$page] = $this->__data['pages'][$file]['properties'];
                    }
                }
            }
        }
        //Get a specific page by path
        else
        {
            if ($file = $this->getLocator()->locate('page:' . $path))
            {
                //Get the relative file path
                $basedir = $this->getLocator()->getBasePath();
                $file    = trim(str_replace($basedir, '', $file), '/');

                //Only include routable pages
                if(isset($this->__data['routes'][$path])) {
                    $result[$path] = $this->__data['pages'][$file]['properties'];
                }
            }
        }

        return $result;
    }

    public function getPage($path)
    {
        $page = false;

        if(!isset($this->__pages[$path]))
        {
            if($file = $this->getLocator()->locate('page:'. $path))
            {
                //Get the relative file path
                $basedir = $this->getLocator()->getBasePath();
                $file    = trim(str_replace($basedir, '', $file), '/');

                //Load the page
                $options = $this->__data['pages'][$file]['properties'] + $this->__data['pages'][$file]['attributes'];
                $page = $this->getObject('com:pages.page.entity', ['data' => $options]);

                //Get the parent
                $parent_path = ltrim(dirname($page->path), '/');

                //Set page default properties from parent collection
                if(!$page->isCollection() && $parent_path && $parent_page = $this->getPage($parent_path))
                {
                    if($parent_page->isCollection() && $parent_page->has('collection/page'))
                    {
                        foreach($parent_page->get('collection/page') as $property => $value) {
                            $page->set($property, $value);
                        }
                    }
                }

                //Set the layout (if not set yet)
                if($page->has('layout'))
                {
                    if (is_string($page->layout)) {
                        $page->layout = new ComPagesObjectConfig(['path' => $page->layout]);
                    } else {
                        $page->layout = new ComPagesObjectConfig($page->layout);
                    }
                }

                //Get the collection
                if($page->isCollection()) {
                    $page->collection = $this->getCollection($page->path);
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

    public function getRoutes($path = null)
    {
        if(!is_null($path)) {
            $result = $this->__data['routes'][$path];
        } else {
            $result = $this->__data['routes'];
        }

        return $result;
    }

    public function isPage($path)
    {
        if(!isset($this->__pages[$path])) {
            $result = (bool) $this->getLocator()->locate('page:'. $path);
        } else {
            $result = ($this->__pages[$path] === false) ? false : true;
        }

        return $result;
    }

    public function isPageAccessible($path)
    {
        $result = true;

        if($page = $this->getPage($path))
        {
            //Groups
            if($page->has('access/groups')) {
                $result = $this->getObject('user')->hasGroup($page->get('access/groups'));
            }

            //Roles
            if($result && $page->has('access/roles')) {
                $result = $this->getObject('user')->hasRole($page->get('access/roles'));
            }
        }
        else $result = false;

        return $result;
    }

    public function loadCache($basedir, $refresh = true)
    {
        if ($refresh || (!$cache = $this->isCached($basedir)))
        {
            $pages       = array();
            $routes      = array();
            $collections = array();
            $redirects   = array();

            //Create the data
            $iterate = function ($dir) use (&$iterate, $basedir, &$pages, &$routes, &$collections, &$redirects)
            {
                $order = false;
                $nodes = array();
                $files = array();

                //Only include pages
                if(is_dir($dir) && !file_exists($dir.'/.ignore'))
                {
                    //List
                    foreach (new DirectoryIterator($dir) as $node)
                    {
                        if (strpos($node->getFilename(), '.order.') !== false && !is_array($order)) {
                            $order = $this->getObject('object.config.factory')->fromFile((string)$node->getFileInfo(), false);
                        } else {
                            $nodes[] = $node->getFilename();
                        }
                    }

                    if(is_array($order)) {
                        //Remove files that don't exist from ordering (to prevent loops)
                        $nodes = array_merge(array_intersect($order, $nodes), $nodes);
                    } else {
                        //Order the files alphabetically
                        natsort($nodes);
                    }

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
                                $path = rtrim(str_replace($basedir, '', $dir . '/' . $info['filename']), '/');

                                if (isset($info['extension']))
                                {
                                    /**
                                     * Variables
                                     *
                                     * Calculate the path specific variables
                                     */
                                    $format = pathinfo($path, PATHINFO_EXTENSION) ?: $info['extension'];
                                    $slug   = pathinfo($path, PATHINFO_FILENAME);

                                    //Handle format
                                    $path = str_replace('.'.$format, '', $path);
                                    $path = $format != 'html' ? $path.'.'.$format : $path;

                                    //Handle index pages
                                    if($slug == 'index')
                                    {
                                        $path = str_replace(array('/index'), '', $path);
                                        $slug = pathinfo($path, PATHINFO_FILENAME);

                                        //If path is empty set to root
                                        $path = empty($path) ?  '/' : $path;
                                    }

                                    /**
                                     * Page
                                     *
                                     * Load and initialise the page object
                                     */
                                    $page = (new ComPagesObjectConfigFrontmatter())->fromFile($file);

                                    $page->append($this->getConfig()->properties);

                                    //Set the file
                                    $page->file = $file;

                                    //Set the path
                                    $page->path = $path;

                                    //Set the slug
                                    $page->slug = $slug;

                                    //Set the format
                                    $page->format = $format;

                                    //Set the hash
                                    $page->hash = $page->getHash();

                                    //Set the date (if not set yet)
                                    if (!isset($page->date)) {
                                        $page->date = filemtime($file);
                                    } else {
                                        $page->date = strtotime($page->date);
                                    }

                                    //Set the process
                                    if (!$page->language) {
                                        $page->language = 'en-GB';
                                    }

                                    //Set the metadata
                                    if(!$page->metadata) {
                                        $page->metadata = array();
                                    }

                                    //Set robots metadata
                                    if(!isset($page->metadata['robots']))
                                    {
                                        if (!$page->getContent() && !$page->layout) {
                                            $page->metadata['robots'] = ['none'];
                                        } else {
                                            $page->metadata['robots'] = array();
                                        }
                                    }
                                    else $page->metadata['robots'] = (array) $page->metadata['robots'];

                                    //Set page type
                                    $page->type = 'page';
                                    foreach($this->getConfig()->types as $type)
                                    {
                                        if($page->get($type, false) !== false)
                                        {
                                            $page->type = $type;
                                            break;
                                        }
                                    }

                                    /**
                                     * Cache
                                     *
                                     * Inject data into the cache
                                     */
                                    $file = trim(str_replace($basedir, '', $file), '/');

                                    //Page
                                    $pages[$file]['properties'] = $page->getProperties(false);
                                    $pages[$file]['attributes'] = $page->getAttributes(false);

                                    //Route
                                    if($page->route !== false) {
                                        $routes[$path] = (array) KObjectConfig::unbox($page->route ?? $path);
                                    }

                                    //File (do not include index pages)
                                    if(strpos($file, '/index.html') === false) {
                                        $files[$path] = $file;
                                    }

                                    //Collection
                                    if(isset($page->collection) && $page->collection !== false)
                                    {
                                        if(!$page->collection->extend && !$page->collection->route) {
                                            $page->collection->route = $path;
                                        }

                                        $collections[$path] = KObjectConfig::unbox($page->collection);
                                    }

                                    //Redirects
                                    if($page->redirect) {
                                        $redirects[$path] = $page->redirect;
                                    }
                                }
                                else
                                {
                                    //Iterate over path
                                    if(false !== $result = $iterate($file)) {
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

            $result['files']       = $iterate($basedir);
            $result['pages']       = $pages;
            $result['routes']      = $routes;
            $result['collections'] = $collections;
            $result['redirects']   = array_flip($redirects);

            //Calculate the hash
            if($this->getConfig()->cache && $this->getConfig()->cache_validation) {
                $result['hash'] = $this->getHash($basedir);
            }

            $this->storeCache($basedir, $result);
        }
        else
        {
            if (!$result = require($cache)) {
                throw new RuntimeException(sprintf('The page registry "%s" cannot be loaded from cache.', $cache));
            }

            //Check if the cache is still valid, if not refresh it
            if($this->getConfig()->cache_validation && $result['hash'] != $this->getHash($basedir)) {
                $this->loadCache($basedir, true);
            }
        }

        return $result;
    }

    public function storeCache($file, $data)
    {
        if($this->getConfig()->cache)
        {
            $path = $this->getConfig()->cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0777, true) && !is_dir($path))) {
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
            $file  = $this->getConfig()->cache_path.'/page_'.$hash.'.php';

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

        if($this->getConfig()->cache)
        {
            $hash   = crc32($file.PHP_VERSION);
            $cache  = $this->getConfig()->cache_path.'/page_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;
        }

        return $result;
    }


}