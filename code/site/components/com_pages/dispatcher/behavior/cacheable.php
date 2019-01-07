<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
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
            'cache_time' => 7200, //2h
        ));

        parent::_initialize($config);
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        if($this->isCacheable())
        {
            if($data = $this->_getCache()->get($this->_getCacheKey()))
            {
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
                        $context->getResponse()->setMaxAge($cache);
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
                    'headers' => $this->getResponse()->getHeaders(),
                    'content' => $content,
                );

                $this->_getCache()->store($data, $this->_getCacheKey());
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

                $this->_getCache()->store($data, $this->_getCacheKey());
            }
        }
    }

    protected function _getCache()
    {
        if (!$this->__cache)
        {
            $options = array(
                'caching'      => true,
                'defaultgroup' => 'com_pages',
                'lifetime'     => 60*24*7, //1 week
            );

            $this->__cache = JCache::getInstance('output', $options);
        }

        return $this->__cache;
    }

    protected function _getCacheKey()
    {
        $url     = $this->getRequest()->getUrl()->toString(KHttpUrl::HOST + KHttpUrl::PATH + KHttpUrl::QUERY);
        $format  = $this->getRequest()->getFormat();
        $user    = $this->getUser()->getId();

        return crc32($url.$format.$user);
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
}