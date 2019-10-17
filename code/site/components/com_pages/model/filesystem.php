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

    protected $_base_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_path      = $config->path;
        $this->_base_path = $config->base_path;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'path'      => '',
            'base_path' =>  Koowa::getInstance()->getRootPath().'/joomlatools-pages',
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
        $data = array();

        if($path = (string) $this->getPath($this->getState()->getValues()))
        {
            if(strpos($path, 'data://') === false)
            {
                $path = $path[0] != '/' ?  $this->_base_path.'/'.$path : $path;
                $data = $this->getObject('object.config.factory')->fromFile($path, false);
            }
            else $tdata = $this->getObject('data.registry')->getData(str_replace('data://', '', (string)$path), false);
       }

       return $data;
    }
}