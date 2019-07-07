<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelFilesystem extends ComPagesModelCollection
{
    protected $_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_path = $config->path;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'path'  => '',
        ]);

        parent::_initialize($config);
    }

    public function getPath(array $variables = array())
    {
        return KHttpUrl::fromTemplate($this->_path, $variables);
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

    public function getData($count = false)
    {
       if(!isset($this->_data))
       {
           if($path = $this->getPath($this->getState()->getValues()))
           {
               if(strpos($path, 'data://') === 0) {
                   $this->_data = $this->getObject('data.registry')->getData(str_replace('data://', '', (string)$path), false);
               } else {
                   $this->_data = $this->getObject('object.config.factory')->fromFile($path, false);
               }
           }
       }

       return (array) $this->_data;
    }
}