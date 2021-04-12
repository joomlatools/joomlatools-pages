<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Http Resource Cache
 *
 * This class implement a simple http resource cache
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 */
class ComPagesHttpCache extends KHttpClient
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'cache'      => false,
            'cache_path' => $this->getObject('pages.config')->getCachePath(),
            'debug'      => $this->getConfig('pages.config')->debug ? true : false,

        ]);

        parent::_initialize($config);
    }

    public function send(KHttpRequestInterface $request)
    {
        //Send the request
        if($this->isCacheable($request))
        {
            $valid = false;
            $url   = $request->getUrl();

            //Load cache
            if($cache = $this->loadCache($url))
            {
                $cache = $this->getObject('http.response')
                    ->setStatus($cache['status'])
                    ->setHeaders($cache['headers'])
                    ->setContent($cache['content']);

                //Check if cache is valid
                $valid = $this->isValid($request, $cache);

                //Cache Validation
                //See: https://tools.ietf.org/html/rfc7234#section-4.3
                if(!$valid && $cache->isValidateable())
                {
                    if($cache->getHeaders()->has('ETag')) {
                        $request->getHeaders()->set('If-None-Match', $cache->getHeaders()->get('ETag'));
                    }

                    if($cache->getHeaders()->has('Last-Modified')) {
                        $request->getHeaders()->set('If-Modified-Since', $cache->getHeaders()->get('Last-Modified'));
                    }
                }
            }

            //Revalidate the cache
            if(!$valid)
            {
                $response = parent::send($request);

                if($cache && ($response->isError() || $response->isNotModified()))
                {
                    $response = $cache;

                    //Refresh the cache
                    $response->setDate(new DateTime('now'));
                }

                //Store response
                if($request->isGet())
                {
                    $this->storeCache($url, [
                        'url'     => rtrim((string) $url, '/'),
                        'status'  => $response->getStatusCode(),
                        'format'  => $response->getFormat(),
                        'headers' => $response->getHeaders()->toArray(),
                        'content' => $response->getContent()
                    ]);
                }
            }
            else $response = $cache;
        }
        else
        {
            $response = parent::send($request);

            //Cache Invalidation
            //See: https://tools.ietf.org/html/rfc7234#section-4.4
            if(!$request->isSafe() && !$response->isError()) {
                $this->deleteCache($request->getUrl());
            }
        }

        return $response;
    }

    public function locateCache($url)
    {
        $key   = crc32((string)$url);
        $file  = $this->getConfig()->cache_path.'/response_'.$key.'.php';

        return $file;
    }

    public function loadCache($url)
    {
        if($this->getConfig()->cache)
        {
            $file = $this->locateCache($url);
            if (is_file($file))
            {
                if (!$data = include($file)) {
                    unlink($file);
                }
            }
        }

        return $data ?? array();
    }

    public function storeCache($url, $data)
    {
        if($this->getConfig()->cache)
        {
            $path = $this->getConfig()->cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0777, true) && !is_dir($path))) {
                throw new RuntimeException(sprintf('The resource cache path "%s" does not exist', $path));
            }

            if(!is_writable($path)) {
                throw new RuntimeException(sprintf('The resource cache path "%s" is not writable', $path));
            }

            if(!is_string($data))
            {
                //Do not cache userinfo
                $location = KHttpUrl::fromString($url)->toString(KHttpUrl::FULL ^ KHttpUrl::USERINFO);

                $result = '<?php /*//url:'.$location.'*/'."\n";
                $result .= 'return '.var_export($data, true).';';
            }

            $file = $this->locateCache($url);

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The url cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function deleteCache($url)
    {
        $result = false;

        $file = $this->locateCache($url);

        if (is_file($file)) {
            $result = unlink($file);
        }

        return $result;
    }

    public function isCacheable(KHttpRequestInterface $request)
    {
        return $this->getConfig()->cache  && $request->isCacheable();
    }

    public function isValid(KHttpRequestInterface $request, KHttpResponseInterface $response)
    {
        if($this->isStale($request, $response) || in_array('no-cache', $request->getCacheControl())) {
            $valid = false;
        } else {
            $valid = true;
        }

        return $valid;
    }

    public function isStale(KHttpRequestInterface $request, KHttpResponseInterface $response)
    {
        $stale = false;
        $cache_control = $request->getCacheControl();

        if(isset($cache_control['max-age']))
        {
            $max_age = (int) $cache_control['max-age'];
            $stale = ($max_age - $response->getAge()) <= 0;
        }

        return $stale;
    }

    public function isDebug()
    {
        return (bool) $this->getConfig()->debug;
    }
}