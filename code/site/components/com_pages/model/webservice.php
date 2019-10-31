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

        if($url = $this->getUrl($this->getState()->getValues())) {
            $data  = $this->getObject('http.client')->get($url);
        }

        return $data;
    }

    protected function _actionPersist(KModelContext $context)
    {
        $result = true;
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

        if($entity->getStatus() == $entity::STATUS_CREATED) {
            $result = $this->getClient()->post($url, $data, $headers);
        }

        if($entity->getStatus() == $entity::STATUS_UPDATED) {
            $result = $this->getClient()->post($url, $data, $headers);
        }

        if($entity->getStatus() == $entity::STATUS_DELETED) {
            $result = $this->getClient()->delete($url, $data, $headers);
        }

        //Reset the entity modified state
        if($result == true) {
            $entity->resetModified();
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