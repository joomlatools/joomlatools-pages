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
                'route'    => array($this, 'buildRoute'),
                'collection' => array($this, 'fetchCollection')
            ),
        ));

        parent::_initialize($config);
    }

    public function loadFile($url)
    {
        $url = $this->qualify($url);

        if(parse_url($url, PHP_URL_SCHEME) == 'page')
        {
            if(!$file = $this->findFile($url)) {
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

    public function parseRoute($route)
    {
        $result = array();

        if($this->isCollection() && isset($this->collection['route']))
        {
            if(!is_array($route)) {
                $segments = explode($route, '/');
            } else {
                $segments = $route;
            }

            $parts    = explode('/', $this->collection['route']);
            $segments = array_values($segments);

            foreach($parts as $key => $name)
            {
                if($name[0] == ':' && isset($segments[$key]))
                {
                    $name = ltrim($name, ':');
                    $result[$name] = $segments[$key];
                }
            }
        };

        return $result;
    }

    public function buildRoute(KModelEntityInterface $entity)
    {
        $route = 'route://'.$this->path;

        if($this->isCollection() && isset($this->collection['route']))
        {
            $parts    = explode('/', $this->collection['route']);
            $segments = array();
            foreach($parts as $key => $name)
            {
                if($name[0] == ':') {
                    $segments[] = $entity->getProperty(ltrim($name, ':'));
                } else {
                    $segments[] = $name;
                }
            }

            $route .= '/'.implode('/', $segments);
        }

        return $route;
    }

    public function isCollection()
    {
        return $this->collection !== false;
    }

    public function fetchCollection()
    {
        if($this->collection !== false)
        {
            if(!$this->_collection)
            {
                $this->_collection = $this->getObject('com:pages.controller.page')
                    ->path($this->path)
                    ->browse();
            }

            return  $this->_collection;
        }

        return array();
    }
}