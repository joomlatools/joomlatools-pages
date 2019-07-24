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

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setChannel($config->channel);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
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
}