<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesPageRegistry extends KObject implements KObjectSingleton
{
    protected $_pages  = array();
    protected $_data   = array();
    protected $_content = array();

    protected $_base_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_base_path = rtrim($config->base_path, '/');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_path' => 'page://pages',
        ));

        parent::_initialize($config);
    }

    public function getPage($path)
    {
        $page = null;

        if(!isset($this->_pages[$path]))
        {
            $url = $this->_base_path . '/' . $path;

            if($file = $this->getObject('template.locator.factory')->locate($url))
            {
                //Load the page
                $page = (new ComPagesPage())->fromFile($file);

                //Set the path
                $page->path = $path;

                //Set the slug
                $page->slug = basename($path);

                //Set the published state (if not set yet)
                if(!isset($page->published)) {
                    $page->published = true;
                }

                //Set the date (if not set yet)
                if(!isset($page->date)) {
                    $page->date = filemtime($page->getFilename());
                }
            }
            else $page = false;

            $this->_pages[$path] = $page;
        }

        return $this->_pages[$path];
    }

    public function hasPage($path)
    {
        if(!isset($this->_pages[$path]))
        {
            $url = $this->_base_path . '/' . $path;
            $result = $this->getObject('template.locator.factory')->locate($url);
        }
        else $result = ($this->_pages[$path] === false) ? false : true;

        return $result;
    }

    public function getData($path)
    {
        $page = $this->getPage($path);

        if($page && $page->isCollection())
        {
            $pages = array();
            $file  = $page->getFilename();

            $iterator = new FilesystemIterator(dirname($file));
            while($iterator->valid())
            {
                $file = pathinfo($iterator->current()->getRealpath(), PATHINFO_FILENAME);

                if($file != 'index') {
                    $pages[] = $this->getPage($path.'/'.$file)->toArray();
                }

                $iterator->next();
            }

            $this->_data[$path] = $pages;
        }
        else $this->_data[$path] = $page->toArray();

        return isset($this->_data[$path]) ? $this->_data[$path] : null;
    }

    public function getContent($path)
    {
        if(!$this->_content[$path])
        {
            $result = false;
            $page   = $this->getPage($path);

            if($page && !$page->isCollection())
            {
                $url     = $this->_base_path.'/'.$path;
                $type    = $page->getFiletype();
                $content = $page->getContent();
                $data    = $this->getData($path);

                $result = $this->getObject('com:pages.template.page')
                    ->loadString($content, $type, $url)
                    ->render($data);

                $this->_content[$path] = $result;
            }
            else $this->_content[$path] = false;
        }

        return $this->_content[$path];
    }

    public function isCollection($path)
    {
        $result = false;
        if($page  = $this->getPage($path)) {
            $result = $page->isCollection();
        }

        return $result;
    }

    public function isAccessible($path)
    {
        $result = true;

        if($page = $this->getPage($path))
        {
            //Check groups
            if($page->access->groups)
            {
                $groups = $this->getObject('com:pages.database.table.groups')
                    ->select($this->getObject('user')->getGroups(), KDatabase::FETCH_ARRAY_LIST);

                $groups = array_map('strtolower', array_column($groups, 'title'));

                if(!array_intersect($groups, $page->access->groups->toArray())) {
                    $result = false;
                }
            }

            //Check roles
            if($result && $page->access->roles)
            {
                $roles = $this->getObject('com:pages.database.table.roles')
                    ->select($this->getObject('user')->getRoles(), KDatabase::FETCH_ARRAY_LIST);

                $roles = array_map('strtolower', array_column($roles, 'title'));

                if(!array_intersect($roles, $page->access->roles->toArray())) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    public function isPublished($path)
    {
        $result = true;

        if($page = $this->getPage($path))
        {
            if($page->published) {
                $result = $page->published;
            }
        }

        return $result;
    }
}