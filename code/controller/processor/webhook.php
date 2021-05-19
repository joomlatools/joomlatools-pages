<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerProcessorWebhook extends ComPagesControllerProcessorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'method'  => KHttpRequest::POST,
            'url'     => '',
            'headers' => [],
            'format' => null,
        ]);

        parent::_initialize($config);
    }

    public function processData(array $data)
    {
        $data = $this->getPayload($data);

        $request = $this->getObject('http.request')
            ->setMethod($this->getMethod())
            ->setUrl($this->getUrl());

        //Set the content
        if(is_array($data))
        {
            $content = http_build_query($data, '', '&');
            $request->setContent($content, 'application/x-www-form-urlencoded');
        }
        else $request->setContent((string) $data, $data->getMediaType());

        //Add the headers
        $request->getHeaders()->add($this->getHeaders(), true);

        //Send content
        return $this->getObject('lib:http.client')->send($request);
    }

    public function getUrl()
    {
        return $this->getConfig()->url;
    }

    public function getMethod()
    {
        return $this->getConfig()->method;
    }

    public function getHeaders()
    {
        $headers = KObjectConfig::unbox($this->getConfig()->headers);
        $headers['Origin'] = $this->getRequest()->getOrigin();

        return $headers;
    }

    public function getPayload(array $data)
    {
        if($format = $this->getConfig()->format)
        {
            $factory = $this->getObject('object.config.factory');

            if($factory->isRegistered($format)) {
                $data = $factory->createFormat($format, $data);
            }
        }

        return $data;
    }
}