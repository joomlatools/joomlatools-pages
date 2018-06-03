<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplatePage extends ComPagesTemplateAbstract
{
    protected $_collection = false;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_path' => 'page://pages',
            'functions' => array(
                'route'    => array($this, 'createRoute'),
                'collection' => array($this, 'loadCollection')
            ),
        ));

        parent::_initialize($config);
    }

    public function loadFile($url)
    {
        $url = $this->qualify($url);

        if(parse_url($url, PHP_URL_SCHEME) == 'page')
        {
            if(!$file = $this->getObject('template.locator.factory')->locate($url)) {
                throw new RuntimeException(sprintf('Cannot find page: "%s"', $url));
            }

            //Load the layout
            $page = (new ComPagesTemplateFile())->fromFile($file);

            //Store the data
            $this->_data = KObjectConfig::unbox($page);

            //Store the filename
            $this->_filename = $file;

            //Load the content
            $result = $this->loadString($page->getContent(), pathinfo($file, PATHINFO_EXTENSION), $url);
        }
        else $result = parent::loadFile($url);

        return $result;
    }

    public function createRoute($path)
    {
        $route = 'route://path='.$this->path.'/'.$this->file;
        if(!empty($path)) {
            $route .= '&'.$path;
        }

        return $route;
    }

    public function loadCollection()
    {
        if($this->collection !== false)
        {
            if(!$this->_collection)
            {
                $this->_collection = $this->getObject('com:pages.controller.page')
                    ->path($this->path.'/'.$this->file)
                    ->browse();
            }

            return  $this->_collection;
        }

        return array();
    }
}