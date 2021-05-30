<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelPages extends ComPagesModelCollection
{
    private $__data;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insertComposite('slug', 'cmd', array('path'), '')
            ->insertInternal('path', 'url');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'type'      => 'pages',
            'behaviors' => ['com:pages.model.behavior.recursable' => ['key' => 'folder']]
        ]);

        parent::_initialize($config);
    }

    public function fetchData()
    {
        if(!isset($this->__data))
        {
            $this->__data = array();

            //If path is not defined to set root path
            $state = $this->getState();
            $path  = $state->path ?? '/';

            if($path)
            {
                $registry = $this->getObject('page.registry');

                if (!$state->isUnique())
                {
                    if(!$state->recurse) {
                        $mode = ComPagesPageRegistry::PAGES_LIST;
                    } else {
                        $mode = ComPagesPageRegistry::PAGES_TREE;
                    }

                    $data = $registry->getPages($path, $mode, (int) $state->level - 1);
                }
                else $data = $registry->getPages($path.'/'.$this->getState()->slug, ComPagesPageRegistry::PAGES_ONLY);

                $this->__data = array_values($data);
            }
        }

        return $this->__data;
    }

    public function filterItem(&$page, KModelStateInterface $state)
    {
        $result = true;

        //For performance reasons check of the page has an access property before checking
        if($result && isset($page['access'])) {
            $result = $this->getObject('page.registry')->isPageAccessible($page['path']);
        }

        return $result;
    }

    protected function _actionHash(KModelContext $context)
    {
        $data = array_column($context->data, 'hash', 'path');
        return hash('crc32b', serialize($data));
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }
}