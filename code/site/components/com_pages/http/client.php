<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesHttpClient extends KHttpClient
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'cache'       => false,
            'cache_path'  => $this->getObject('com://site/pages.config')->getSitePath('cache'),
            'cache_time'  => 60*60*24, //1 day https://tools.ietf.org/html/rfc7234#section-4.2.2
            'cache_force' => false,
        ]);

        parent::_initialize($config);
    }

    public function send(KHttpRequestInterface $request)
    {
        $url = $request->getUrl();

        if($request->isCacheable())
        {
            //Cache exists
            if($file = $this->isCached($url, false))
            {
                if (!$cache = require($file)) {
                    throw new RuntimeException(sprintf('The response "%s" cannot be loaded from cache.', $file));
                }

                $cache = $this->getObject('http.response')
                    ->setHeaders($cache['headers'])
                    ->setContent($cache['content']);

                if($request->isGet()) {
                    $response = $this->_validateGet($request, $cache);
                } else {
                    $response = $this->_validateHead($request, $cache);
                }
            }
            else
            {
                $response = parent::send($request);

                //Storing response in cache
                //See: https://tools.ietf.org/html/rfc7234#section-3
                if($response->isCacheable() || ($this->getConfig()->cache_force && !$response->isError()))
                {
                    $this->storeCache($url, [
                        'headers' => $response->getHeaders()->toArray(),
                        'content' => $response->getContent()
                    ]);
                }
            }
        }
        else
        {
            //Cache Invalidatoon
            //See: https://tools.ietf.org/html/rfc7234#section-4.4
            $response = parent::send($request);

            //Remove the cached file if requests successful
            if(!$request->isSafe() && !$response->isError())
            {
                if($cache = $this->isCached($url)) {
                    unlink($cache);
                }
            }
        }

        return $response;
    }

    public function _validateGet(KHttpRequestInterface $request, KHttpResponseInterface $cache)
    {
        $url = $request->getUrl();

        //Cache is not fresh
        if(!$this->isCached($url) || ($cache->isStale() && !$this->getConfig()->cache_force))
        {
            //Cache Validation
            //See: https://tools.ietf.org/html/rfc7234#section-4.3
            if($cache->getContent() && $cache->isValidateable())
            {
                if($cache->getHeaders()->has('ETag')) {
                    $request->getHeaders()->set('If-None-Match', $cache->getHeaders()->get('ETag'));
                }

                if($cache->getHeaders()->has('Last-Modified')) {
                    $request->getHeaders()->set('If-Modified-Since', $cache->getHeaders()->get('Last-Modified'));
                }

                //Send validation request
                $response = parent::send($request);

                //Handle 304 Not Modified
                if($response->isNotModified())
                {
                    //Freshening stored responses upon validation
                    //See: https://tools.ietf.org/html/rfc7234#section-4.3.4
                    $response->setHeaders(array_merge($cache->getHeaders()->toArray(), $response->getHeaders()->toArray()));
                    $response->setContent($cache->getContent());
                }
            }
            //Unconditional GET request
            else $response = parent::send($request);

            //Update the cache
            $this->storeCache($url, [
                'headers' => $response->getHeaders()->toArray(),
                'content' => $response->getContent()
            ]);
        }
        //Cache is fresh
        else
        {
            $response = $this->getObject('http.response')
                ->setHeaders($cache->getHeaders()->toArray())
                ->setContent($cache->getContent());
        }

        return $response;
    }

    public function _validateHead(KHttpRequestInterface $request, KHttpResponseInterface $cache)
    {
        $url = $request->getUrl();

        //Cache is not fresh
        if(!$this->isCached($url) || ($cache->isStale() && !$this->getConfig()->cache_force))
        {
            $response = parent::send($request);

            //Freshening stored responses via HEAD
            //See: https://tools.ietf.org/html/rfc7234#section-4.3.5
            if($cache->isValidateable())
            {
                if($cache->getHeaders()->has('ETag'))
                {
                    if ($cache->getHeaders()->get('ETag') != $response->getHeaders()->get('ETag')) {
                        $cache['content'] = '';
                    }
                }

                if($cache->getHeaders()->has('Last-Modified'))
                {
                    if ($cache->getHeaders()->get('Last-Modified') != $response->getHeaders()->get('Last-Modified')) {
                        $cache['content'] = '';
                    }
                }
            }

            $response->setHeaders(array_merge($cache->getHeaders()->toArray(), $response->getHeaders()->toArray()));

            //Update the cache
            $this->storeCache($url, [
                'headers' => $response->getHeaders()->toArray(),
                'content' => $cache->getContent()
            ]);
        }
        //Cache is fresh
        else
        {
            $response = $this->getObject('http.response')
                ->setHeaders($cache->getHeaders()->toArray());
        }

        return $response;
    }

    public function storeCache($url, $data)
    {
        if($this->getConfig()->cache)
        {
            $path = rtrim($this->getConfig()->cache_path, '/');

            if(!is_dir($path) && (false === @mkdir($path, 0755, true) && !is_dir($path))) {
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

            $hash = crc32($url.PHP_VERSION);
            $file  = $path.'/resource_'.$hash.'.php';

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The url cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function isCached($url, $fresh = true)
    {
        $result = false;

        if($this->getConfig()->cache)
        {
            $hash = crc32($url.PHP_VERSION);
            $path = rtrim($this->getConfig()->cache_path, '/');

            $cache  = $path.'/resource_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result && $fresh)
            {
                //Refresh cache if it expired
                if((time() - filemtime($result)) > $this->getConfig()->cache_time) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}