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
    static private $__resource_cache = array();

    private $__http;
    private $__data;

    protected $_cache;
    protected $_url;
    protected $_data_path;
    protected $_hash_key;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__http = $config->http;

        $this->_cache = $config->cache;
        $this->_url   = $config->url;

        $this->_data_path = $config->data_path;
        $this->_hash_key  = $config->hash_key;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'persistable'  => true,
            'identity_key' => 'id',
            'http'         => 'com://site/pages.http.cache',
            'entity'       => 'resource',
            'url'          => '',
            'data_path'    => '',
            'hash_key'     => '',
            'cache'         => true,
            'headers'       => array()
        ]);

        parent::_initialize($config);
    }

    public function getUrl($template, array $variables = array())
    {
        return KHttpUrl::fromTemplate($template, $variables);
    }

    public function getHeaders($cache = true)
    {
        $headers = KObjectConfig::unbox($this->getConfig()->headers);

        if($cache !== true)
        {
            if($cache !== false)
            {
                //Convert max_age to seconds
                if(!is_numeric($cache))
                {
                    if($max_age = strtotime($cache)) {
                        $max_age = $max_age - strtotime('now');
                    }
                }
                else $max_age = $cache;

                $headers['Cache-Control'] = ['max-age' => (int) $max_age];
            }
            else  $headers['Cache-Control'] = ['no-store'];
        }

        return $headers;
    }

    public function getHash($refresh = false)
    {
        $hash = parent::getHash();
        $data = array();

        if($url = $this->getUrl($this->_url, $this->getState()->getValues()))
        {
            $cache = $refresh ? 60 : $this->_cache;
            $data  = $this->fetchResource($url, $cache, $this->_data_path);

            if($this->_hash_key)
            {
                $data = array_column($data, $this->_hash_key, $this->getIdentityKey());

                if($this->getState()->isUnique())
                {
                    $identity = $this->getState()->get($this->getIdentityKey());

                    if(isset($data[$identity])) {
                        $data = $data[$identity];
                    }
                }
            }

            $hash = hash('crc32b', serialize($data).$url);
        }

        return $hash;
    }

    public function fetchData()
    {
        if(!isset($this->__data))
        {
            $data = array();

            if($url = $this->getUrl($this->_url, $this->getState()->getValues())) {
                $data = $this->fetchResource($url, $this->_cache, $this->_data_path);
            }

            $this->__data = $data;
        }

        return $this->__data;
    }

    public function fetchResource($url, $cache = true, $path = null)
    {
        $http = $this->_getHttpClient();

        try
        {
            $key = md5((string) $url);

            if(!isset(self::$__resource_cache[$key]))
            {
                $headers = $this->getHeaders($cache);
                $data    = $http->get($url, $headers);

                self::$__resource_cache[$key] = $data;
            }
            else $data = self::$__resource_cache[$key];
        }
        catch(KHttpException $e)
        {
            //Re-throw exception if in debug mode
            if($http->isDebug()) {
                throw $e;
            } else {
                $data = array();
            }
        }

        if($path)
        {
            if(is_string($path)) {
                $segments = explode('/', $path);
            } else {
                $segments = KObjectConfig::unbox($path);
            }

            foreach($segments as $segment)
            {
                if(!isset($data[$segment])) {
                    $data = array(); break;
                } else {
                    $data = $data[$segment];
                }
            }
        }

        return $data;
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

        $headers = $this->getHeaders();
        $headers['Origin'] = $url->toString(KHttpUrl::AUTHORITY);

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
}