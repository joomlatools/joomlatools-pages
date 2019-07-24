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
            'cache_path' =>  $this->getObject('com:pages.config')->getSitePath().'/cache',
            'cache_time'        => 60*15,   //15min
            'cache_time_shared' => 60*60*2, //2h
        ));

        parent::_initialize($config);
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        if($this->isCacheable())
        {
            if($file = $this->isCached($this->cacheKey()))
            {
                //Require the file from cache
                $data = require $file;

                $content = $this->_prepareContent($data['content']);
                $headers = $this->_prepareHeaders($data['headers']);

                $response = clone $this->getResponse();
                $response
                    ->setHeaders($headers)
                    ->setContent($content);

                //Send the response and terminate the request
                if(!$response->isStale()) {
                    $response->send();
                }
            }
        }
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        if($this->isCacheable())
        {
            //Disable caching
            if ($page = $context->getRequest()->query->get('page', 'url', false))
            {
                $cache = $this->getObject('page.registry')
                    ->getPage($page)
                    ->process->get('cache', true);

                if ($cache !== false)
                {
                    if(is_int($cache)) {
                        $context->getResponse()->setMaxAge($this->getConfig()->cache_time, $cache);
                    }
                }
                else $this->getConfig()->cache = false;
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
                    'content' => $content,
                );

                $this->storeCache($this->cacheKey(), $data);
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
                    'content' => $content
                );

                $this->storeCache($this->cacheKey(), $data);
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

    public function cacheKey()
    {
        $url     = $this->getRouter()->getCanonicalUrl()->toString(KHttpUrl::HOST + KHttpUrl::PATH + KHttpUrl::QUERY);
        $format  = $this->getRequest()->getFormat();
        $user    = $this->getUser()->getId();

        return 'path:'.$url.'#format:'.$format.'#user:'.$user;
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
            $file  = $path.'/document_'.$hash.'.php';

            if(@file_put_contents($file, $result) === false) {
                throw new RuntimeException(sprintf('The document cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function isCached($key)
    {
        $result = false;

        if($this->getConfig()->cache)
        {
            $hash   = crc32($key.PHP_VERSION);
            $cache  = $this->getConfig()->cache_path.'/document_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result)
            {
                if(((time() - filemtime($cache)) > 60*24*7)) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}