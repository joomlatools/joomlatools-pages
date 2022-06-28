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
    protected $_flatten;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_path    = $config->path;
        $this->_flatten = $config->flatten;

        if(filter_var($this->_flatten, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) !== null) {
            $this->_flatten = filter_var($this->_flatten, FILTER_VALIDATE_BOOL);
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key' => 'id',
            'path'         => '',
            'search'       => [], //properties to allow searching on
            'flatten'      => false,
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

    public function getPath(array $variables = array())
    {
        return KHttpUrl::fromTemplate($this->_path, $variables);
    }

    public function filterItem(&$item, KModelStateInterface $state)
    {
        foreach($state->getValues(true) as $key => $value)
        {
            if(isset($item[$key]) && !in_array($item[$key], (array) $value)) {
                return false;
            }
        }

        return true;
    }

    public function filterData($data)
    {
        $key = $this->getIdentityKey();

        foreach($data as $k => $v) {
            $data[$k][$key] = $k;
        }

        $data = array_values($data);

        return parent::filterData($data);
    }


    public function fetchData()
    {
        if(!isset($this->__data))
        {
            $path = (string) $this->getPath($this->getState()->getValues());
            $data = $this->getObject('data.registry')->fromPath($path);

            if($this->_flatten) {
                $data = $data->flatten(is_string($this->_flatten) ? $this->_flatten : null);
            }

            $this->__data = $data->toArray();
        }

       return $this->__data;
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }

    protected function _actionHash(KModelContext $context)
    {
        $hash = parent::_actionHash($context);

        $path = $this->getPath($this->getState()->getValues());
        $hash = $this->getObject('data.registry')->fromPath($path);

        return $hash;
    }
}