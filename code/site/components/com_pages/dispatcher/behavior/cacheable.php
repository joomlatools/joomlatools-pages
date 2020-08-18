<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorCacheable extends KDispatcherBehaviorCacheable
{
    private $__collections;

    /**
     * Cache HIT status codes
     *
     * The page was found in the cache. It has been served from the cache
     */
    const CACHE_HIT = 'HIT';

    //The page was validated, eg HIT, VALIDATED
    const CACHE_VALIDATED = 'VALIDATED';

    //The page was served from the static cache, eg HIT, STATIC
    const CACHE_STATIC = 'STATIC';

    /**
     * Cache MISS status codes
     *
     * The page has been (re-)generated
     */
    const CACHE_MISS = 'MISS';

    //The page was found in cache but has since expired.
    const CACHE_EXPIRED = 'EXPIRED';

    //The page was found in cache but has since been modified.
    const CACHE_MODIFIED = 'MODIFIED';

    /**
     * Cache Dynamic status codes
     *
     * The page settings don't allow the resource to be cached.
     */
    const CACHE_DYNAMIC  = 'DYNAMIC';

    //The page was removed from the cache, eg DYNAMIC, PURGED
    const CACHE_PURGED = 'PURGED';

    //Application debug mode is enabled, eg DYNAMIC, DEBUG
    const CACHE_DEBUG = 'DEBUG';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'cache'      => false,
            'cache_path' =>  $this->getObject('com://site/pages.config')->getSitePath('cache'),
            'cache_time'        => false, //static
            'cache_time_shared' => false, //static
        ));

        parent::_initialize($config);
    }

    protected function _actionValidate(KDispatcherContextInterface $context)
    {
        $cache = $this->loadCache();

        if($cache && $this->isValidatable())
        {
            $response = clone $this->getResponse();
            $response
                ->setStatus($cache['status'])
                ->setHeaders($cache['headers'])
                ->setContent($cache['content']);

            $valid = $this->validateCache($cache);
            $stale = $response->isStale();

            if($valid !== false && !$stale)
            {
                $response->headers->set('Cache-Status', self::CACHE_HIT);

                //Refresh cache if explicitly valid
                if($valid === true)
                {
                    $response->setDate(new DateTime('now'));
                    $response->headers->set('Age', null);
                    $response->headers->set('Cache-Status', self::CACHE_VALIDATED, false);
                }
                else $response->headers->set('Age', max(time() - $response->getDate()->format('U'), 0));

                $cache['headers'] = $response->headers->toArray();
                $this->storeCache($cache);

                // Send the request if nothing has been send yet, or terminate otherwise
                if(!headers_sent()) {
                    $response->send();
                } else {
                    $response->terminate();
                }
            }
            else
            {
                $context->response->headers->set('Cache-Status', self::CACHE_MISS);

                if($response->isStale()) {
                    $context->response->headers->set('Cache-Status', self::CACHE_EXPIRED, false);
                } else {
                    $context->response->headers->set('Cache-Status', self::CACHE_MODIFIED, false);
                }
             }
        }
        else
        {
            //Force a collection cache refresh
            if($cache) {
                $this->validateCache($cache, true);
            }

            $context->response->headers->set('Cache-Status', self::CACHE_MISS);
        }
    }

    protected function _actionCache(KDispatcherContextInterface $context)
    {
        $result   = false;
        $response = $context->getResponse();

        if($response->isCacheable())
        {
            //Reset the date and last-modified
            $response->setDate(new DateTime('now'));
            $response->setLastModified($response->getDate());

            if($cache = $this->loadCache())
            {
                //If the cache exists and it has not been modified do not reset the Last-Modified date
                if($cache['headers']['Etag'] == $response->getEtag()) {
                    $response->setLastModified(new DateTime($cache['headers']['Last-Modified']));
                }
            }

            //Get the page data
            $page = [
                'path'     => $this->getRoute()->getPage()->path,
                'hash'     => $this->getRoute()->getPage()->hash,
                'language' => $this->getRoute()->getPage()->language,
            ];

            $data = array(
                'id'         => $this->getContentLocation()->toString(KHttpUrl::PATH + KHttpUrl::QUERY),
                'url'         => rtrim((string) $this->getContentLocation(), '/'),
                'page'        => $page,
                'collections' => $this->getCollections(),
                'status'      => !$response->isNotModified() ? $response->getStatusCode() : '200',
                'token'       => $this->getCacheToken(),
                'format'      => $response->getFormat(),
                'headers'     => $response->headers->toArray(),
                'content'     => (string) $response->getContent(),
            );

            $result  = $this->storeCache($data);
        }

        return $result;
    }

    protected function _actionPurge(KDispatcherContextInterface $context)
    {
        $result = false;

        $context->getResponse()->headers->set('Cache-Status', self::CACHE_DYNAMIC);

        if($result = $this->deleteCache()) {
            $context->getResponse()->headers->set('Cache-Status', self::CACHE_PURGED, false);
        }

        return $result;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        //Validate the cache
        if($this->isCacheable()) {
            $this->validate();
        } else {
            $this->purge();
        }
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        $response = $context->getResponse();

        if($this->isCacheable())
        {
            $page_time = $this->getPage()->process->get('cache', true);
            $page_time = is_string($page_time) ? strtotime($page_time) - strtotime('now') : $page_time;

            if(is_int($page_time))
            {
                $cache_time = $this->getConfig()->cache_time;
                $cache_time = !is_numeric($cache_time) ? strtotime($cache_time) : $cache_time;

                //Set the max age if defined
                $max        = $cache_time < $page_time ?  $cache_time : $page_time;
                $max_shared = $page_time;

                $response->setMaxAge($max, $max_shared);
            }

            //Set the content collection
            if($collections = $this->getCollections())
            {
                $collections = array_unique(array_column($collections, 'type'));
                $response->headers->set('Content-Collections', implode(',',  $collections));
            }
        }
        else $response->headers->set('Cache-Control', ['no-store']);

        parent::_beforeSend($context);
    }

    protected function _beforeTerminate(KDispatcherContextInterface $context)
    {
        //Store the response in the cache, only is the dispatcher is not being decorated
        if($this->isCacheable() && !$this->isDecorated()) {
            $this->cache();
        }
    }

    public function getCacheToken()
    {
        if($cache = $this->loadCache()) {
            $token = $cache['token'];
        } else {
            $token = bin2hex(random_bytes('16'));
        }

        return $token;
    }

    public function getCollections()
    {
        if(!isset($this->__collections))
        {
            foreach($this->getObject('model.factory')->getModels() as $name => $model)
            {
                $this->__collections[] = [
                    'name' => $name,
                    'type' => $model->getType(),
                    'hash' => $model->getHash()
                ];
            }
        }

        return (array) $this->__collections;
    }

    public function getContentLocation()
    {
        /**
         * If Content-Location is included in a 2xx (Successful) response message and its value refers (after
         * conversion to absolute form) to a URI that is the same as the effective request URI, then the recipient
         * MAY consider the payload to be a current representation of that resource at the time indicated by the
         * message origination date.  For a GET (Section 4.3.1) or HEAD (Section 4.3.2) request, this is the same
         * as the default semantics when no Content-Location is provided by the server.
         */

        if(!$location = $this->getResponse()->headers->get('Content-Location'))
        {
            $location = $this->getRequest()->getUrl()
                ->toString(KHttpUrl::SCHEME + KHttpUrl::HOST + KHttpUrl::PATH + KHttpUrl::QUERY);
        }

        //See: https://tools.ietf.org/html/rfc7231#section-3.1.4.2
        return  $this->getObject('http.url', ['url' => $location]);
    }

    public function locateCache($url = null)
    {
        $key  = $url ?? $this->getContentLocation()->toString(KHttpUrl::PATH + KHttpUrl::QUERY);
        $file = $this->getConfig()->cache_path . '/response_' . crc32($key) . '.php';

        return $file;
    }

    public function loadCache()
    {
        static $data;

        if(!isset($data) && $this->getConfig()->cache)
        {
            $file = $this->locateCache();
            if (is_file($file))
            {
                if (!$data = include($file)) {
                    unlink($file);
                }
            }
        }

        return $data ?? array();
    }

    public function validateCache($cache, $refresh = false)
    {
        static $collections;

        $valid = false;

        //Validate the page
        if($cache['page']['hash'] == $this->getObject('page.registry')->getPage($cache['page']['path'])->hash)
        {
            $valid = true;

            foreach($cache['collections'] as $collection)
            {
                if(!isset($collections[$collection['name']]))
                {
                    //If the collection has a hash validate it
                    if($collection['hash'])
                    {
                        $model = $this->getObject('model.factory')->createModel($collection['name']);

                        if($collection['hash'] != $model->getHash($refresh)) {
                            $valid = false;
                        }
                    }
                    else $valid = null;

                    $collections[$collection['name']] = $valid;
                }
                else $valid =  $collections[$collection['name']];

                if($valid !== true) {
                    break;
                }
            }
        }

        return $valid;
    }

    public function storeCache($data)
    {
        if($this->getConfig()->cache)
        {
            $url  = (string) $this->getContentLocation();
            $path = $this->getConfig()->cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0777, true) && !is_dir($path))) {
                throw new RuntimeException(sprintf('The document cache path "%s" does not exist', $path));
            }

            if(!is_writable($path)) {
                throw new RuntimeException(sprintf('The document cache path "%s" is not writable', $path));
            }

            if(!is_string($data))
            {
                $result = '<?php /*//url='.$url.'*/'."\n";
                $result .= 'return '.var_export($data, true).';';
            }

            $file = $this->locateCache();

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The document cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function deleteCache()
    {
        $result = false;

        $file = $this->locateCache();

        if (is_file($file)) {
            $result = unlink($file);
        }

        return $result;
    }

    public function isCacheable()
    {
        if($result = parent::isCacheable())
        {
            if($page = $this->getPage()) {
                $result = (bool)$page->process->get('cache', true);
            } else {
                $result = false;
            }
        }

        return $result;
    }

    public function isValidatable()
    {
        //Can only validate if cacheable, cache exists and the request allows for cache re-use
        return $this->isCacheable() && !in_array('no-cache', $this->getRequest()->getCacheControl());
    }
}