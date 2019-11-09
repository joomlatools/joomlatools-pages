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
    private $__client;

    protected $_url;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__client = $config->client;

        $this->_url = $config->url;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'client'       => 'http.client',
            'identity_key' => 'id',
            'entity'       => 'resource',
            'url'          => '',
        ]);

        parent::_initialize($config);
    }

    public function getUrl(array $variables = array())
    {
        return KHttpUrl::fromTemplate($this->_url, $variables);
    }

    public function setState(array $values)
    {
        //Automatically create states that don't exist yet
        foreach($values as $name => $value)
        {
            if(!$this->getState()->has($name)) {
                $this->getState()->insert($name, 'string');
            }
        }

        return parent::setState($values);
    }

    public function fetchData($count = false)
    {
        $data = array();

        if($url = $this->getUrl($this->getState()->getValues()))
        {
            try {
                $data = $this->getObject('http.client')->get($url);
            } catch(KHttpExceptionNotFound $e) {
                $data = array();
            }
        }

        return $data;
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result      = true;
        $identity_key = $this->getIdentityKey();

        $entity = $context->entity;

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
                $result = $this->getClient()->put($url, $data, $headers);
            } else {
                $result = $this->getClient()->post($url, $data, $headers);
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
            $result = $this->getClient()->patch($url, $data, $headers);

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
            $result = $this->getClient()->delete($url, $data, $headers);

            if($result !== false) {
                $result = self::PERSIST_SUCCESS;
            } else {
                $result = self::PERSIST_FAILURE;
            }
        }

        return $result;
    }

    public function getClient()
    {
        if(!($this->__client instanceof KHttpClientInterface))
        {
            $this->__client = $this->getObject($this->__client);

            if(!$this->__client instanceof KHttpClientInterface)
            {
                throw new UnexpectedValueException(
                    'Http cient: '.get_class($this->__client).' does not implement  KHttpClientInterface'
                );
            }
        }

        return $this->__client;
    }
}