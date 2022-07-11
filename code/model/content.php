<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelContent extends ComPagesModelData
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insertComposite('slug', 'cmd', array('folder'), '')
            ->insertInternal('folder', 'url', is_string($this->_path) ? $this->_path : '/' );
    }

    public function getPaths()
    {
        $state  = $this->getState();

        $paths = (array) $this->_path;
        if($folder = trim($state->folder, '/'))
        {
            $folder = '/'.$folder;

            if(in_array($folder, $paths)) {
                $paths = array($folder);
            }
        }

        $result = [];
        foreach($paths as $path)
        {
            $path = trim($path, '/');
            $result[$path] = $this->_namespace ? $this->_namespace .'://'.$path : $path;
        }

        return $result;
    }

    public function fetchData()
    {
        if(!isset($this->__data))
        {
            $this->__data = array();

            $state  = $this->getState();

            if (!$state->isUnique())
            {
                $paths = $this->getPaths();

                $data  = [];
                foreach($paths as $folder => $path)
                {
                    if($items = $this->getObject('data.registry')->fromPath($path, false, false))
                    {
                        array_walk($items, function(&$item, $slug) use($folder, $path)
                        {
                            $item['folder'] = '/'.$folder;
                            $item['path']   = $path.'/'.$slug;
                            $item['slug']   = $slug;
                        });

                        $data += $items;
                    }
                }
            }
            else
            {
                $folder = trim($state->folder, '/');
                $slug   = $state->slug;

                $path = $folder ? $folder.'/'.$slug : $slug;
                $path = $this->_namespace ? $this->_namespace .'://'.$path : $path;

                $data = [];
                if($item = $this->getObject('data.registry')->fromFile($path, false))
                {
                    $item['folder'] = '/'.$folder;
                    $item['path']   = $path;
                    $item['slug']   = $slug;

                    $data[] = $item;
                }
            }

            $this->__data = array_values($data);

        }

        return $this->__data;
    }
}