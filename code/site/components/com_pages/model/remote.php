<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelRemote extends ComPagesModelCollection
{
    protected $_url;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_url = $config->url;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'url'  => '',
        ]);

        parent::_initialize($config);
    }

    public function getUrl(array $variables)
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

    public function getData()
    {
       if(!isset($this->_data))
       {
           $state = $this->getState();

           if($url = $this->getUrl($state->getValues())) {
               $this->_data = $this->getObject('com:pages.data.client')->fromUrl($url, false);
           }
       }

       return (array) $this->_data;
    }
}