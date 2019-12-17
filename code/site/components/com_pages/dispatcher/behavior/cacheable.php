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

    //The page was served from cache
    const CACHE_HIT     = 'HIT';

    //The page was not found in cache and has been generated
    const CACHE_MISS    = 'MISS';

    //The origin server instructed to bypass cache via a `Cache-Control` header set to `no-cache`
    const CACHE_BYPASS  = 'BYPASS';

    //The page was found in cache but has since expired. It has been generated
    const CACHE_EXPIRED = 'EXPIRED';

    //The page was found in cache and couldn't be validated
    const CACHE_MODIFIED = 'MODIFIED';

    //The resource was found in cache and was validated. It has been served from the cache
    const CACHE_REFRESHED = 'REFRESHED';

    //The page settings don't allow the resource to be cached.  Instead, the page was generated
    const CACHE_DYNAMIC  = 'DYNAMIC';

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getObject('event.publisher')
            ->addListener('onAfterApplicationRespond', array($this, 'onAfterApplicationRespond'));
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'cache'      => false,
            'cache_path' =>  $this->getObject('com://site/pages.config')->getSitePath('cache'),
            'cache_time'        => 60*15,   //15min
            'cache_time_shared' => 60*60*2, //2h
            'cache_validation'  => true,
        ));

        parent::_initialize($config);
    }

    public function isSupported()
    {
        //Always enabled if caching is enabled
        return $this->getConfig()->cache;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        if($this->isCacheable())
        {
            if($file = $this->isCached($this->getCacheKey()))
            {
                //Require the file from cache
                $data = require $file;

                $content     = $this->_prepareContent($data['content']);
                $headers     = $this->_prepareHeaders($data['headers']);
                $page        = $data['page'];
                $collections = $data['collections'];

                $response = clone $this->getResponse();
                $response
                    ->setHeaders($headers)
                    ->setContent($content);

                if(!$this->isBypass() && (!$response->isStale() || $this->isValid($page, $collections) === true))
                {
                    $response->getHeaders()->set('Cache-Status', self::CACHE_HIT);

                    //Refresh the cache
                    if(!$response->isError() && ($response->isNotModified() || $response->isStale()))
                    {
                        $response->setDate(new DateTime('now'));

                        $data['headers']['Date'] = (string) $response->getHeaders()->get('Date');
                        $this->storeCache($this->getCacheKey(), $data);

                        $response->getHeaders()->set('Cache-Status', self::CACHE_REFRESHED);
                    }
                    else $response->getHeaders()->set('Age', $response->getAge());

                    //Terminate the request
                    $response->send();
                }
                else
                {
                    if($this->isBypass()) {
                        $context->response->getHeaders()->set('Cache-Status', self::CACHE_BYPASS);
                    } elseif($response->isStale()) {
                        $context->response->getHeaders()->set('Cache-Status', self::CACHE_EXPIRED);
                    } else {
                        $context->response->getHeaders()->set('Cache-Status', self::CACHE_MODIFIED);
                    }
                }
            }
            else $context->response->getHeaders()->set('Cache-Status', self::CACHE_MISS);
        }
        else $context->response->getHeaders()->set('Cache-Status', self::CACHE_MISS);
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        if($this->isCacheable())
        {
            //Disable caching
            if ($page = $context->page)
            {
                $cache = $page->process->get('cache', true);

                if ($cache !== false)
                {
                    //Set the max age if defined
                    if(is_int($cache))
                    {
                        $max        = $this->getConfig()->cache_time < $cache ? $this->getConfig()->cache_time : $cache;
                        $max_shared = $cache;

                        $context->getResponse()->setMaxAge($max, $max_shared);
                    }

                    //Set the cache tags
                    if($collections = $this->getCollections())
                    {
                        $tags = array_unique(array_column($collections, 'type'));
                        $context->getResponse()->getHeaders()->set('Cache-Tag', implode(',',  $tags));
                    }
                }
                else
                {
                    $this->getConfig()->cache = false;
                    $context->response->getHeaders()->set('Cache-Status', self::CACHE_DYNAMIC);
                }
            }
        }
        else $context->getResponse()->getHeaders()->set('Cache-Status', self::CACHE_MISS);

        parent::_beforeSend($context);
    }

    protected function _beforeTerminate(KDispatcherContextInterface $context)
    {
        $response = $this->getResponse();

        //Proxy Koowa Output
        if($this->isCacheable() && $response->isCacheable())
        {
            if($content = $response->getContent())
            {
                //Remove blank empty lines
                $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);

                //Get the page data
                $page = [
                    'path' => $this->getRoute()->getPage()->path,
                    'hash' => $this->getRoute()->getPage()->hash
                ];

                $data = array(
                    'page'        => $page,
                    'collections' => $this->getCollections(),
                    'headers'     => $response->getHeaders()->toArray(),
                    'content'     => (string) $content,
                );

                $this->storeCache($this->getCacheKey(), $data);
            }
        }
    }

    public function onAfterApplicationRespond(KEventInterface $event)
    {
        $response = $this->getResponse();

        //Proxy Joomla Output
        if($this->isCacheable() && $response->isCacheable())
        {
            if($content = $event->getTarget()->getBody())
            {
                $headers = array();
                foreach (headers_list() as $header)
                {
                    $parts = explode(':', $header, 2);
                    $headers[trim($parts[0])] = trim($parts[1]);
                }

                //Remove blank empty lines
                $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);

                //Get the page data
                $page = [
                    'path' => $this->getRoute()->getPage()->path,
                    'hash' => $this->getRoute()->getPage()->hash
                ];

                $data = array(
                    'page'        => $page,
                    'collections' => $this->getCollections(),
                    'headers'     => $headers,
                    'content'     => (string) $content
                );

                $this->storeCache($this->getCacheKey(), $data);
            }
        }
    }

    protected function _prepareContent($content)
    {
        //Search for a token in the content and refresh it
        $token       = JSession::getFormToken();
        $search      = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
        $replacement = '<input type="hidden" name="' . $token . '" value="1" />';

        return preg_replace($search, $replacement, $content);
    }

    protected function _prepareHeaders($headers)
    {
        unset($headers['Expires']);

        return $headers;
    }

    public function getCacheKey()
    {
        $url     = trim($this->getRequest()->getUrl()->toString(KHttpUrl::HOST + KHttpUrl::PATH + KHttpUrl::QUERY), '/');
        $format  = $this->getRequest()->getFormat();
        $user    = $this->getUser()->getId();

        return 'url:'.$url.'#format:'.$format.'#user:'.$user;
    }

    public function getCollections()
    {
        if(!isset($this->__collections))
        {
            foreach($this->getObject('com://site/pages.model.factory')->getCollections() as $name => $collection)
            {
                $this->__collections[] = [
                    'name' => $name,
                    'type' => $collection->getType(),
                    'hash' => $collection->getHash()
                ];
            }
        }

        return (array) $this->__collections;
    }

    public function storeCache($key, $data)
    {
        if($this->getConfig()->cache)
        {
            $path = $this->getConfig()->cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0755, true) && !is_dir($path))) {
                throw new RuntimeException(sprintf('The document cache path "%s" does not exist', $path));
            }

            if(!is_writable($path)) {
                throw new RuntimeException(sprintf('The document cache path "%s" is not writable', $path));
            }

            if(!is_string($data))
            {
                $result = '<?php /*//request:'.$key.'*/'."\n";
                $result .= 'return '.var_export($data, true).';';
            }

            $hash = crc32($key.PHP_VERSION);
            $file  = $path.'/response_'.$hash.'.php';

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The document cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function isCached($key, $fresh = true)
    {
        $result = false;

        if($this->getConfig()->cache)
        {
            $hash   = crc32($key.PHP_VERSION);
            $cache  = $this->getConfig()->cache_path.'/response_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result && $fresh)
            {
                if(((time() - filemtime($cache)) > 60*24*7)) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    public function isValid($page, $collections)
    {
        $valid = true;

        if($this->getConfig()->cache_validation)
        {
            //Validate the page
            if($page['hash'] == $this->getObject('page.registry')->getPage($page['path'])->hash)
            {
                foreach($collections as $collection)
                {
                    //If the collection has a hash validate it
                    if($collection['hash'])
                    {
                        $model = $this->getObject('com://site/pages.model.factory')->createCollection($collection['name']);

                        if($collection['hash'] != $model->getHash()) {
                            $valid = false; break;
                        }
                    }
                    else $valid = null; break;
                }
            }
            else $valid = false;
        }

        return $valid;
    }

    public function isBypass()
    {
        return in_array('no-cache', $this->getRequest()->getCacheControl());
    }
}