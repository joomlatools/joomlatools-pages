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
            'url' => '',
        ]);

        parent::_initialize($config);
    }

    public function processData(array $data)
    {
        $url = $this->getUrl();

        $request = $this->getObject('http.request')
            ->setMethod($this->getMethod())
            ->setUrl($url)
            ->setContent(http_build_query($data, '', '&'), 'application/x-www-form-urlencoded');

        $request->getHeaders()->add($this->getHeaders());

        $this->getObject('lib:http.client')->send($request);
    }

    public function getUrl()
    {
        return $this->getConfig()->url;
    }

    public function getMethod()
    {
        return KHttpRequest::POST;
    }

    public function getHeaders()
    {
        $headers = array();
        $headers['Origin'] = $this->getRequest()->getOrigin();

        return $headers;
    }
}