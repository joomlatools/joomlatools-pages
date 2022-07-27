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

    protected $_url;
    protected $_cache_time;
    protected $_data_path;
    protected $_hash_key;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__http = $config->http;
        $this->_url   = $config->url;

        $this->_cache_time = $config->cache;
        $this->_data_path  = $config->data_path;
        $this->_hash_key   = (array) KObjectConfig::unbox($config->hash_key);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'persistable'  => true,
            'identity_key' => 'id',
            'http'         => 'com:pages.http.cache',
            'url'          => '',
            'data_path'    => '',
            'hash_key'     => array(),
            'cache'        => true,
            'cache_path'   => null,
            'headers'      => array(),
             'search'      => [], //properties to allow searching on
        ])->append([
            'behaviors'   => [
                'com:pages.model.behavior.paginatable',
                'com:pages.model.behavior.sortable',
                'com:pages.model.behavior.sparsable',
                'com:pages.model.behavior.filterable',
                'com:pages.model.behavior.searchable' => ['columns' => $config->search],
            ],
        ]);

        parent::_initialize($config);
    }

    protected function _initializeContext(KModelContext $context)
    {
        //Set a minimum cache time of 1min if refreshing
        if($context->action == 'hash' && $this->_cache_time !== false) {
            $this->_cache_time = $context->refresh ? 60 : $this->_cache_time;
        }

        parent::_initializeContext($context);
    }

    public function getUrl(array $variables = array())
    {
        return KHttpUrl::fromTemplate($this->_url, $variables);
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

    public function fetchData()
    {
        if(!isset($this->__data))
        {
            $data = array();

            if($url = $this->getUrl($this->getState()->getValues())) {
                $data = $this->_getHttpData($url, $this->_cache_time, $this->_data_path);
            }

            $this->__data = $data;
        }

        return $this->__data;
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }

    protected function _actionHash(KModelContext $context)
    {
        $data = $this->fetchData();

        $url      = $this->getUrl($this->getState()->getValues());
        $identity = $this->getState()->get($this->getIdentityKey());

        if($this->_hash_key)
        {
            $result = array();
            foreach($this->_hash_key as $key)
            {
                $column = array_column($data, $key, $this->getIdentityKey());

                if($this->getState()->isUnique() && isset($column[$identity])) {
                    $column = $column[$identity];
                }

                $result[$key] = $column;
            }

            $data = $result;
        }

        $hash = hash('crc32b', serialize($data).$url);
        return $hash;
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result = true;
        $entity = $context->entity;

        $http    = $this->_getHttpClient();
        $url     = $this->getUrl($this->getState()->getValues());
        $headers = ['Origin' => $url->toString(KHttpUrl::AUTHORITY)];

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

    protected function _getHttpClient()
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

            //Set the cache path
            if($this->getConfig()->cache_path) {
                $this->__http->getConfig()->cache_path = $this->getConfig()->cache_path;
            }
        }

        return $this->__http;
    }

    protected function _getHttpData($url, $cache = true, $path = null)
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
}