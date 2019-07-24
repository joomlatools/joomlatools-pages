<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDataClient extends KObject implements KObjectSingleton
{
    protected $_cache;
    protected $_cache_path;
    protected $_cache_time;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Set the cache
        $this->_cache = $config->cache;

        //Set the cache time
        $this->_cache_time = $config->cache_time;

        if(empty($config->cache_path)) {
            $this->_cache_path = $this->getObject('com:pages.config')->getSitePath('cache');
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

    public function fromUrl($url, $object = true, KHttpRequest $request = null)
    {
        $config = null;

        if(!$cache = $this->isCached($url))
        {
            if(!ini_get('allow_url_fopen')) {
                throw new \RuntimeException('Cannot use a stream transport when "allow_url_fopen" is disabled.');
            }

            if(!$request) {
                $request = $this->getObject('http.request');
            }

            $version = $this->getObject('com:pages.version');
            $context = stream_context_create(array('http' => array(
                'user_agent' => 'Joomlatools/Pages/'.$version,
                'protocol_version' => 1.1,
                'header'           => (string) $request->getHeaders()->set('Connection', 'close')
            )));

            if($headers = get_headers($url, true, $context))
            {
                $status = explode(' ', $headers[0], 3);
                $response = $this->getObject('http.response', [
                    'status_code'    => $status[1],
                    'status_message' => $status[2],
                    'headers'        => $headers
                ]);

                $factory = $this->getObject('object.config.factory');
                $format  = $response->getFormat();

                //Request failed
                if(!$response->isSuccess())
                {
                    throw new RuntimeException(
                        sprintf('Cannot get data from url, error: "%s %s"', $response->getStatusCode(), $response->getStatusMessage()
                    ));
                }

                if($content = file_get_contents($url, false, $context)) {
                    $config = $factory->createFormat($format)->fromString($content, $object);
                }
            }
            else
            {
                if($error = error_get_last()) {
                    throw new RuntimeException(sprintf('Cannot get data from url, error: "%s"', trim($error['message'])));
                } else {
                    throw new RuntimeException(sprintf('Cannot get data from url: "%s"', $url));
                }
            }

            $this->storeCache($url, KObjectConfig::unbox($config));
        }
        else
        {
            if (!$config = require($cache)) {
                throw new RuntimeException(sprintf('The data "%s" cannot be loaded from cache.', $cache));
            }
        }

        return $config;
    }

    public function storeCache($url, $data)
    {
        if($this->_cache)
        {
            $path = $this->_cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0755, true) && !is_dir($path))) {
                throw new RuntimeException(sprintf('The url cache path "%s" does not exist', $path));
            }

            if(!is_writable($path)) {
                throw new RuntimeException(sprintf('The url cache path "%s" is not writable', $path));
            }

            if(!is_string($data))
            {
                //Do not cache userinfo
                $location = KHttpUrl::fromString($url)->toString(KHttpUrl::FULL ^ KHttpUrl::USERINFO);

                $result = '<?php /*//url:'.$location.'*/'."\n";
                $result .= 'return '.var_export($data, true).';';
            }

            $hash = crc32($url.PHP_VERSION);
            $file  = $this->_cache_path.'/remote_'.$hash.'.php';

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The url cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function isCached($url)
    {
        $result = false;

        if($this->_cache)
        {
            $hash   = crc32($url.PHP_VERSION);
            $cache  = $this->_cache_path.'/remote_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result)
            {
                //Refresh cache if it expired
                if((time() - filemtime($result)) > $this->_cache_time) {
                    $result = false;
                }
            }
        }

        return $result;
    }

}