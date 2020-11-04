<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelWebservice extends ComPagesModelCollection
{
    private $__http;
    private $__data;

    protected $_url;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__http = $config->http;
        $this->_url   = $config->url;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'persistable'  => true,
            'identity_key' => 'id',
            'http'         => 'com://site/pages.http.cache',
            'entity'       => 'resource',
            'url'          => '',
            'data'         => '',
            'cache'        => null,
        ]);

        parent::_initialize($config);
    }

    public function getUrl(array $variables = array())
    {
        return KHttpUrl::fromTemplate($this->_url, $variables);
    }

    public function getHash($refresh = false)
    {
        $hash = null;

        if($url = $this->getUrl($this->getState()->getValues()))
        {
            $http    = $this->_getHttpClient();

            $max_age = $refresh ? 0 : $this->getConfig()->cache;
            $headers = ['Cache-Control' => $this->_getCacheControl($max_age)];

            try
            {
                $content = $http->get($url, $headers);
                $hash    = hash('crc32b', $content.$url);
            }
            catch(KHttpException $e)
            {
                //Re-throw exception if in debug mode
                if($http->isDebug()) {
                    throw $e;
                } else {
                    $hash = null;
                }
            }
        }

        return $hash;
    }

    public function fetchData($count = false)
    {
        if(!isset($this->__data))
        {
            $this->__data = array();

            if($url = $this->getUrl($this->getState()->getValues()))
            {
                $http    = $this->_getHttpClient();

                $max_age = $this->getConfig()->cache;
                $headers = ['Cache-Control' => $this->_getCacheControl($max_age)];

                try {
                    $this->__data = $http->get($url, $headers);
                }
                catch(KHttpException $e)
                {
                    //Re-throw exception if in debug mode
                    if($http->isDebug()) {
                        throw $e;
                    } else {
                        $this->__data = array();
                    }
                }
            }

            if($path = $this->getConfig()->data)
            {
                if(is_string($path)) {
                    $segments = explode('/', $path);
                } else {
                    $segments = KObjectConfig::unbox($path);
                }

                foreach($segments as $segment)
                {
                    if(!isset($this->__data[$segment]))
                    {
                        $this->__data = array();
                        break;
                    }
                    else $this->__data = $this->__data[$segment];
                }
            }
        }

        return $this->__data;
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result = true;
        $entity = $context->entity;

        $http    = $this->_getHttpClient();
        $url     = $this->getUrl($this->getState()->getValues());
        $headers = ['Origin' => $url->toString(KHttpUrl::AUTHORITY)];


        $data   = array();
        if(!$context->state->isUnique())
        {
            foreach($context->entity as $entity) {
                $data = array_merge($entity->getProperties(true), $data);
            }
        }
        else $data = $entity->getProperties(true);

        if($entity->getStatus() == $entity::STATUS_CREATED)
        {
            if($context->state->isUnique()) {
                $result = $http->put($url, $data, $headers);
            } else {
                $result = $http->post($url, $data, $headers);
            }

            if($result !== false)
            {
                foreach($result as $name => $value) {
                    $entity->setProperty($name, $value, false);
                }

                $result = self::PERSIST_SUCCESS;
            }
            else $result = self::PERSIST_FAILURE;
        }

        if($entity->getStatus() == $entity::STATUS_UPDATED)
        {
            $result = $http->patch($url, $data, $headers);

            if($result !== false)
            {
                if(!empty($result))
                {
                    foreach($result as $name => $value) {
                        $entity->setProperty($name, $value, false);
                    }

                    $result = self::PERSIST_SUCCESS;
                }
                else  $result = self::PERSIST_NOCHANGE;
            }
            else $result = self::PERSIST_FAILURE;
        }

        if($entity->getStatus() == $entity::STATUS_DELETED)
        {
            $result = $http->delete($url, $data, $headers);

            if($result !== false) {
                $result = self::PERSIST_SUCCESS;
            } else {
                $result = self::PERSIST_FAILURE;
            }
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

    protected function _getCacheControl($max_age = null)
    {
        $cache_control = array();

        if($max_age !== null)
        {
            if($max_age !== false)
            {
                //Convert max_age to seconds
                if(!is_numeric($max_age))
                {
                    if($max_age = strtotime($max_age)) {
                        $max_age = $max_age - strtotime('now');
                    }
                }

                $cache_control = ['max-age' => (int) $max_age];
            }
            else $cache_control = ['no-store'];
        }

        return $cache_control;
    }
}