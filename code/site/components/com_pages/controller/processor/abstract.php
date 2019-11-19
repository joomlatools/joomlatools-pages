<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesControllerProcessorAbstract extends KObject implements ComPagesControllerProcessorInterface
{
    protected $_channel;

    private $__request;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setChannel($config->channel);

        $this->setRequest($config->request);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'request'  => null,
            'channel'  => 'form'
        ]);

        parent::_initialize($config);
    }

    public function setChannel($name)
    {
        $this->_channel = $name;
        return $this;
    }

    public function getChannel()
     {
         return $this->_channel;
     }

    public function setRequest(KControllerRequestInterface $request)
    {
        $this->__request = $request;
        return $this;
    }

    public function getRequest()
    {
        return $this->__request;
    }

    public function __clone()
    {
        parent::__clone();

        $this->__request   = clone $this->__request;
    }
}