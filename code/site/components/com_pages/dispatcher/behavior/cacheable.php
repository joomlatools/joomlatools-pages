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
    private $__validators;

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
        if($this->isValidatable())
        {
            $response = clone $this->getResponse();

            //Get validators from response
            $etag       = $context->request->getEtag();
            $validators = $this->_decodeEtag($etag);

            //Initialise the response object
            if($response->isCacheable() && $this->loadCache())
            {
                $cache = $this->loadCache();

                $response
                    ->setStatus($cache['status'])
                    ->setHeaders($cache['headers'])
                    ->setContent($cache['content']);

                $response->headers->set('Cache-Status', self::CACHE_HIT);
            }
            else $response->setEtag($etag);

            //Check if the cache is valid
            $valid = $this->validateCache($validators);

            //Check if response is stale
            $stale = $response->isStale();

            //Check the if the cache is fresh
            if($this->isRefreshable() && !in_array('must-revalidate', $response->getCacheControl())) {
                $fresh = ($valid === true || $stale !== true);
            } else {
                $fresh = ($valid !== false && $stale !== true);
            }

            if($fresh)
            {
                //Refresh response if valid
                if($valid === true)
                {
                    $response->setDate(new DateTime('now'));
                    $response->headers->set('Age', null);
                    $response->headers->set('Cache-Status', self::CACHE_VALIDATED, false);
                    $response->headers->set('Content-Location', (string) $this->getContentLocation());
                }
                else $response->headers->set('Age', max(time() - $response->getDate()->format('U'), 0));

                //Refresh cache if cacheable
                if($response->isCacheable())
                {
                    $cache['headers'] = $response->headers->toArray();
                    $this->storeCache($cache);
                }

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

                if($stale) {
                    $context->response->headers->set('Cache-Status', self::CACHE_EXPIRED, false);
                } else {
                    $context->response->headers->set('Cache-Status', self::CACHE_MODIFIED, false);
                }
             }
        }
        else
        {
            /*
             * Force refresh validators (in case they are calculated from cache)
             *
             * The "no-cache" request directive indicates that a cache MUST NOT use a stored response to
             * satisfy the request without successful validation on the origin server.
             *
             * See: https://tools.ietf.org/html/rfc7234#section-5.2.1.4
             */
            if($cache = $this->loadCache()) {
                $this->validateCache($cache['validators'], true);
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

            $data = array(
                'id'          => $this->getContentLocation()->toString(KHttpUrl::PATH + KHttpUrl::QUERY),
                'url'         => rtrim((string) $this->getContentLocation(), '/'),
                'validators'  => $this->getCacheValidators(),
                'status'      => !$response->isNotModified() ? $response->getStatusCode() : '200',
                'token'       => $this->getCacheToken(),
                'format'      => $response->getFormat(),
                'headers'     => $response->headers->toArray(),
                'content'     => (string) $response->getContent(),
                'language'    => $this->getRoute()->getPage()->language,
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
        if($this->isCacheable())
        {
            //Set the max age if defined in for the page
            $page_time = $this->getPage()->process->get('cache', true);
            $page_time = is_string($page_time) ? strtotime($page_time) - strtotime('now') : $page_time;

            if(is_int($page_time))
            {
                $cache_time = $this->getConfig()->cache_time;

                if($cache_time !== false)
                {
                    $cache_time = !is_numeric($cache_time) ? strtotime($cache_time) : $cache_time;
                    $max        = $cache_time < $page_time ?  $cache_time : $page_time;

                    $context->response->setMaxAge($max, $page_time);
                }
                else $context->response->setMaxAge($page_time);

                $context->response->headers->set('Cache-Control', ['must-revalidate'], false);
            }

            $this->validate();
        }
        else $this->purge();
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        //Get the response from the context
        $response = $context->getResponse();

        if($this->isCacheable())
        {
            //Set the content collections
            if($models = $this->getObject('model.factory')->getModels())
            {
                $collections = array();
                foreach($models as $model) {
                    $collections[] = $model->getType();
                }

                $collections = array_unique($collections);
                $response->headers->set('Content-Collections', implode(',',  $collections));
            }

            //Set the weak etag
            $validators = $this->getCacheValidators();
            $response->setEtag($this->_encodeEtag($validators), false);
        }
        else $response->headers->set('Cache-Control', ['no-store']);
    }

    protected function _beforeTerminate(KDispatcherContextInterface $context)
    {
        //Store the response in the cache, only is the dispatcher is not being decorated
        if($this->isCacheable() && $context->response->isCacheable() && !$this->isDecorated()) {
            $this->cache();
        }
    }

    public function getCacheValidators($refresh = false)
    {
        if(!isset($this->__validators) || $refresh)
        {
            $validators = array();

            //Add hash (ensure the etag is unique)
            $validators['hash'] = $this->getHash();

            //Add user
            $validators['user'] = $this->getUser()->getId();

            //Add page
            $validators['page'] = [
                'path' => $this->getRoute()->getPage()->path,
                'hash' => $this->getRoute()->getPage()->hash
            ];

            //Add collections
            foreach($this->getObject('model.factory')->getModels() as $name => $model) {
                $validators['collections'][$name] = $model->getHash();
            }

            $this->__validators = $validators;
        }

        return $this->__validators;
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

    public function getContentLocation()
    {
        /**
         * Get content location
         *
         * If Content-Location is included in a 2xx (Successful) response message and its value refers (after
         * conversion to absolute form) to a URI that is the same as the effective request URI, then the recipient
         * MAY consider the payload to be a current representation of that resource at the time indicated by the
         * message origination date.  For a GET (Section 4.3.1) or HEAD (Section 4.3.2) request, this is the same
         * as the default semantics when no Content-Location is provided by the server.
         *
         * See: https://tools.ietf.org/html/rfc7231#section-3.1.4.2
         */
        if(!$location = $this->getResponse()->headers->get('Content-Location'))
        {
            $location = $this->getRequest()->getUrl()
                ->toString(KHttpUrl::SCHEME + KHttpUrl::HOST + KHttpUrl::PATH + KHttpUrl::QUERY);
        }


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

    public function validateCache($validators, $refresh = false)
    {
        static $collections;

        $valid = false;

        if(is_array($validators) && !empty($validators))
        {
            $valid = true;

            //Validate page
            $page = $this->getObject('page.registry')->getPage($validators['page']['path']);
            if($validators['page']['hash'] != $page->hash) {
                $valid = false;
            }

            //Validate user
            $user = $this->getUser();
            if($valid && $validators['user'] != $user->getId()) {
               $valid = false;
            }

            //Validate collections
            if($valid && isset($validators['collections']))
            {
                foreach($validators['collections'] as $name => $hash)
                {
                    if(!isset($collections[$name]))
                    {
                        //If the collection has a hash validate it
                        if($hash)
                        {
                            $model = $this->getObject('model.factory')->createModel($name);

                            if($hash != $model->getHash($refresh)) {
                                $valid = false;
                            }
                        }
                        else $valid = null;

                        $collections[$name] = $valid;
                    }
                    else $valid =  $collections[$name];

                    if($valid !== true) {
                        break;
                    }
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
        //Can only validate cache if cacheable and the request allows for cache re-use
        return $this->isCacheable() && !in_array('no-cache', $this->getRequest()->getCacheControl());
    }

    public function isRefreshable()
    {
        //Can only refresh cache if cacheable and the request allows for cache re-refreshing
        return $this->isCacheable() && !in_array('must-revalidate', $this->getRequest()->getCacheControl());
    }

    protected function _encodeEtag(array $validators)
    {
        $data = json_encode($validators);
        $etag = base64_encode(gzdeflate($data));

        return $etag;
    }

    protected function _decodeEtag($etag)
    {
        $validators = array();
        if($etag && $data = base64_decode($etag)) {
            $validators = json_decode(gzinflate($data), true);
        }

        return $validators;
    }
}