<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesConfig extends KObject implements KObjectSingleton
{
    protected $_base_paths;
    protected $_site_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_base_paths = $config->base_paths;
        $this->_site_path  = $config->site_path;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_paths'     => [
                'cache'      => null,
                'data'       => null,
                'extensions' => null,
                'logs'       => null,
                'theme'      => null,
            ],
            'site_path' => Koowa::getInstance()->getRootPath().'/joomlatools-pages'
        ));
    }

    public function getSitePath($path = null)
    {
        //If the site path is empty do not try to return a path
        if($this->_site_path && $path)
        {
            if(!$result = $this->_base_paths[$path]) {
                $result = $this->_site_path.'/'.$path;
            }
        }
        else $result = $this->_site_path;

        return $result;
    }
}