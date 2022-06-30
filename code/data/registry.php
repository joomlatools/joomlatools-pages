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
    private $__http;
    private $__data    = array();
    private $__locator = null;
    private $__namespaces = array();
    private $__hashes     = array();

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the locator
        $this->__locator = $this->getObject('com:pages.data.locator');

        //Set the namespaces
        $this->__namespaces = KObjectConfig::unbox($config->namespaces);

        //Set http client
        $this->__http = $config->http;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'http'       => 'com:pages.http.cache',
            'cache'      => $this->getObject('pages.config')->debug ? false : true,
            'cache_path' => $this->getObject('pages.config')->getCachePath(),
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

    public function getHash($path = null)
    {
        //Find path and base_path
        if ($namespace = parse_url($path, PHP_URL_SCHEME)) {
            $base_path = $this->__namespaces[$namespace];
        } else {
            $base_path = $this->getLocator()->getConfig()->base_path;
        }

        $this->getLocator()->setBasePath($base_path);

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

        if(!isset($this->__hashes[$path]))
        {
            if($file = $this->getLocator()->locate($path)) {
                $this->__hashes[$path] =  hash('crc32b', serialize( $size($file)));
            }  else {
                $this->__hashes[$path] = false;
            }
        }

        return $this->__hashes[$path];
    }

    public function fromUrl($url, $cache = true, $object = true, $content = true)
    {
        if (!isset($this->__data[$url]) || !$object)
        {
            $http = $this->_getHttpClient();
            $headers['Cache-Control'] = $this->_getCacheControl($cache);

            try
            {
                $data = $http->get($url, $headers);
            }
            catch(KHttpException $e)
            {
                //Re-throw exception if in debug mode
                if($http->isDebug()) {
                    throw $e;
                } else {
                    $data = null;
                }
            }

            //Try to create data object based on format from string
            if(is_string($data))
            {
                if($format = pathinfo ($url, PATHINFO_EXTENSION))
                {
                    if($this->getObject('object.config.factory')->isRegistered($format))
                    {
                        $data = $this->getObject('object.config.factory')->createFormat($format)->fromString($data);

                        if($content && $data instanceof ComPagesObjectConfigMarkdown) {
                            $data->content = $data->getContent();
                        }

                        $data = $data->toArray();
                    }
                }
            }

            if(!is_array($data)) {
                throw new \RuntimeException(sprintf('Url: %s cannot be parsed to structured data', $url));
            }

            if($object)
            {
                $class = $this->getObject('manager')->getClass('com:pages.data.object');
                $data = new $class($data);
            }

            $this->__data[$url] = $data;
        }
        else $data = $this->__data[$url];

        return $data;
    }

    public function fromPath($path, $object = true, $content = true)
    {
        if(!isset($this->__data[$path]) || !$object)
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
            $key       = crc32($base_path . $root_path);

            if (!isset($this->__data[$key]))
            {
                $data = $this->loadCache($root_path, false, $namespace ?: 'data', $content);
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

            if($object)
            {
                $class = $this->getObject('manager')->getClass('com:pages.data.object');
                $data = new $class($data);
            }

            $this->__data[$path] = $data;
        }
        else $data = $this->__data[$path];

        return $data;
    }

    public function fromFile($path, $object = true, $content = true)
    {
        $result = array();

        //Find path and base_path
        if ($namespace = parse_url($path, PHP_URL_SCHEME))
        {
            $path = str_replace($namespace . '://', '', $path);
            $base_path = $this->__namespaces[$namespace];
        }
        else $base_path = $this->getLocator()->getConfig()->base_path;

        $this->getLocator()->setBasePath($base_path);

        if($file = $this->getLocator()->locate($path)) {
            $result =  $this->__fromFile($file, $content);
        }

        if($object)
        {
            $class = $this->getObject('manager')->getClass('com:pages.data.object');
            $result = new $class($result);
        }

        return $result;
    }

    private function __fromPath($path, $content = true)
    {
        //Locate the data file
        if (!$file = $this->getLocator()->locate($path)) {
            throw new InvalidArgumentException(sprintf('The data path "%s" does not exist.', $path));
        }

        if(is_dir($file)) {
            $result = $this->__fromDirectory($file, $content);
        } else {
            $result = $this->__fromFile($file, $content);
        }

        return $result;
    }

    private function __fromFile($file, $content = true)
    {
        $data = $this->getObject('object.config.factory')->fromFile($file);

        if($data instanceof ComPagesObjectConfigMarkdown) {

            if($content) {
                $data->content = $data->getContent();
            }

            $data->hash = $data->getHash();

        }

        return $data->toArray();
    }

    private function __fromDirectory($path, $content = true)
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
                $data[$name] = $this->__fromPath($file, $content);
            } else {
                $data = $this->__fromPath($file, $content);
            }
        }

        foreach($dirs as $name => $dir) {
            $data[$name] = $this->__fromPath($dir, $content);
        }

        return $data;
    }

    public function loadCache($path, $refresh = true, $namespace = 'data', $content = true)
    {
        $file = $this->getLocator()->getBasePath().'/'.$path;

        if ($refresh || !$cache = $this->isCached($file))
        {
            $data = $this->__fromPath($path, $content);

            $result = array();
            $result['data'] = $data;

            //Calculate the hash
            if($this->getConfig()->cache && $this->getConfig()->cache_validation) {
                $result['hash'] = $this->getHash($path);
            }

            $this->storeCache($file, $result, $namespace);
        }
        else
        {
            if (!$result = require($cache)) {
                throw new RuntimeException(sprintf('The data "%s" cannot be loaded from cache.', $cache));
            }

            //Check if the cache is still valid, if not refresh it
            if($this->getConfig()->cache_validation && $result['hash'] != $this->getHash($path)) {
                $this->loadCache($path, true, $namespace, $content);
            }

            $data = $result['data'];
        }

        return $data;
    }

    public function storeCache($file, $data, $namespace = 'data')
    {
        if($this->getConfig()->cache)
        {
            $path = $this->getConfig()->cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0777, true) && !is_dir($path))) {
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
            $file  = $this->getConfig()->cache_path.'/'.$namespace.'_'.$hash.'.php';

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

    public function _getHttpClient()
    {
        if(!($this->__http instanceof KHttpClientInterface))
        {
            $this->__http = $this->getObject($this->__http);

            if(!$this->__http instanceof KHttpClientInterface)
            {
                throw new UnexpectedValueException(
                    'Http client: '.get_class($this->__http).' does not implement  KHttpClientInterface'
                );
            }
        }

        return $this->__http;
    }

    protected function _getCacheControl($cache)
    {
        $cache_control = array();

        if($cache !== true)
        {
            if($cache !== false)
            {
                //Convert max_age to seconds
                if(!is_numeric($cache))
                {
                    if($max_age = strtotime($cache)) {
                        $max_age = $max_age - strtotime('now');
                    }
                }
                else $max_age = $cache;

                $cache_control = ['max-age' => (int) $max_age];
            }
            else $cache_control = ['no-store'];
        }

        return $cache_control;
    }
}
