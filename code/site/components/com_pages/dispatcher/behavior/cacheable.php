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
    protected $_tag_timestamps;

    //The resource was found in cache
    const CACHE_HIT     = 'HIT';

    //The resource was not found in cache
    //and has been generated
    const CACHE_MISS    = 'MISS';

    //The resource was found in cache
    //but has since expired and was generated
    const CACHE_EXPIRED = 'EXPIRED';

    //The resource was found in cache
    //but has since been invalidated and was generated
    const CACHE_INVALID  = 'INVALID';

    //The origin server instructed to bypass cache
    //via a Cache-Control header set to no-cache, or max-age=0.
    const CACHE_BYPASS  = 'BYPASS';

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

                $content = $this->_prepareContent($data['content']);
                $headers = $this->_prepareHeaders($data['headers']);

                $response = clone $this->getResponse();
                $response
                    ->setHeaders($headers)
                    ->setContent($content);

                if($response->isStale() || $this->isValid($response))
                {
                    if($response->isStale()) {
                        $context->response->getHeaders()->set('Cache-Status', self::CACHE_EXPIRED);
                    }

                    if($this->isValid($response)) {
                        $context->response->getHeaders()->set('Cache-Status', self::CACHE_INVALID);
                    }
                }
                else
                {
                    //Send the response and terminate the request
                    $response->getHeaders()->set('Cache-Status', self::CACHE_HIT);
                    $response->send();
                 }
            }
            else $context->response->getHeaders()->set('Cache-Status', self::CACHE_MISS);
        }
        else $context->response->getHeaders()->set('Cache-Status', self::CACHE_MISS);
    }

    protected function _afterPost(KDispatcherContextInterface $context)
    {
        $response = $context->getResponse();

        if($response->isSuccess() && $response->getStatusCode() != KHttpResponse::NO_CONTENT)
        {
            $type = $context->getSubject()->getController()->getModel()->getType();
            $this->updateTagTimestamp($type);
        }
    }

    protected function _afterPut(KDispatcherContextInterface $context)
    {
        $response = $context->getResponse();

        if($response->isSuccess() && $response->getStatusCode() != KHttpResponse::NO_CONTENT)
        {
            $type = $context->getSubject()->getController()->getModel()->getType();
            $this->updateTagTimestamp($type);
        }
    }

    protected function _afterPatch(KDispatcherContextInterface $context)
    {
        $response = $context->getResponse();

        if($response->isSuccess() && $response->getStatusCode() != KHttpResponse::NO_CONTENT)
        {
            $type = $context->getSubject()->getController()->getModel()->getType();
            $this->updateTagTimestamp($type);
        }
    }

    protected function _afterDelete(KDispatcherContextInterface $context)
    {
        if($context->esponse->isSuccess())
        {
            $type = $context->getSubject()->getController()->getModel()->getType();
            $this->updateTagTimestamp($type);
        }
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
                    if(is_int($cache)) {
                        $context->getResponse()->setMaxAge($this->getConfig()->cache_time, $cache);
                    }

                    //Set the cache tags
                    $context->getResponse()->getHeaders()->set('Cache-Tag', implode(',',  $this->getCacheTag()));
                }
                else $this->getConfig()->cache = false;
            }
        }

        //Set cache status headers
        if(!$this->isCacheable())
        {
            if(!$context->getRequest()->isCacheable()) {
                $context->getResponse()->getHeaders()->set('Cache-Status', self::CACHE_BYPASS);
            } else {
                $context->getResponse()->getHeaders()->set('Cache-Status', self::CACHE_MISS);
            }
        }

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
                $data = array(
                    'headers' => $response->getHeaders()->toArray(),
                    'content' => (string) $content,
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

                $data = array(
                    'headers' => $headers,
                    'content' => (string) $content
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
        $url     = rtrim($this->getRequest()->getUrl()->toString(KHttpUrl::HOST + KHttpUrl::PATH + KHttpUrl::QUERY), '/');
        $format  = $this->getRequest()->getFormat();
        $user    = $this->getUser()->getId();

        return 'path:'.$url.'#format:'.$format.'#user:'.$user;
    }

    public function getCacheTag()
    {
        $tags = array();

        foreach($this->getObject('com://site/pages.model.factory')->getCollections() as $collection)
        {
            if($type = $collection->getType()) {
                $tags[] = $collection->getType();
            }
        }

        return array_unique($tags);
    }

    public function loadTagTimestamps()
    {
        if(!isset($this->_tag_timestaps))
        {
            if($file = $this->isCached('timestamps', 'tags', false)) {
                $timestamps = require $file;
            } else {
                $timestamps = array();
            }

            $this->_tag_timestamps = $timestamps;
        }

        return $this->_tag_timestamps;
    }

    public function updateTagTimestamp($tag)
    {
        $timestamps = $this->loadTagTimestamps();
        $timestamps[$tag] = time();

        $this->storeCache('timestamps', $timestamps, 'tags');
    }

    public function storeCache($key, $data, $prefix = 'document')
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
            $file  = $path.'/'.$prefix.'_'.$hash.'.php';

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The document cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function isCached($key, $prefix = 'document', $fresh = true)
    {
        $result = false;

        if($this->getConfig()->cache)
        {
            $hash   = crc32($key.PHP_VERSION);
            $cache  = $this->getConfig()->cache_path.'/'.$prefix.'_'.$hash.'.php';
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

    public function isValid(KDispatcherResponseInterface $response)
    {
        $valid = false;
        if($tags = $response->getHeaders()->get('Cache-Tag'))
        {
            $tags       = explode(',', $tags);
            $timestamps = $this->loadTagTimestamps();

            foreach($tags as $tag)
            {
                //If the tag is stale
                if(isset($timestamps[$tag]) && $timestamps[$tag] > $response->getDate()->getTimestamp()) {
                    $valid = true; break;
                }
            }
        }

        return $valid;
    }
}