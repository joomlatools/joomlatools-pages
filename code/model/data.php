<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelData extends ComPagesModelCollection
{
    private $__data;

    protected $_path;
    protected $_namespace;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_path      = KObjectConfig::unbox($config->path);
        $this->_namespace = $config->namespace;

        $this->getState()
            ->insertComposite('slug', 'cmd', array('folder'), '')
            ->insertInternal('folder', 'url', '/');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'path'       => '',
            'namespace' => null,
            'search'    => [], //properties to allow searching on
        ])->append([
            'behaviors'   => [
                'com:pages.model.behavior.paginatable',
                'com:pages.model.behavior.sortable',
                'com:pages.model.behavior.sparsable',
                'com:pages.model.behavior.filterable',
                'com:pages.model.behavior.searchable' => ['columns' => $config->search],
            ],
        ]);

        parent::_initialize($config);
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
                        array_walk($items, function(&$item) use($folder) {
                            $item['folder'] = '/'.$folder;
                        });

                        $data += $items;
                    }
                }
            }
            else
            {
                $folder = trim($state->folder, '/');
                $slug   = $state->slug;

                $path = $folder.'/'.$slug;
                $path = $this->_namespace ? $this->_namespace .'://'.$path : $path;

                if($data = $this->getObject('data.registry')->fromFile($path, false))
                {
                    $data['folder'] = '/'.$folder;
                    $data = array($slug => $data);
                }
            }

            $this->__data = $data;
        }

       return $this->__data;
    }

    public function filterData($data)
    {
        array_walk($data, function(&$item, $slug)
        {
            $item['slug'] = $slug;

            if(isset($item['content'])) {
                $item['hash'] = hash('crc32b', $item['content']);
            }

        });

        $data = array_values($data);

        return parent::filterData($data);
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }

    protected function _actionHash(KModelContext $context)
    {
        $hashes = array();
        $paths = $this->getPaths();
        foreach($paths as $path) {
            $hashes[] = $this->getObject('data.registry')->getHash($path);
        }

        $hash = hash('crc32',serialize($hashes));

        return $hash;
    }
}