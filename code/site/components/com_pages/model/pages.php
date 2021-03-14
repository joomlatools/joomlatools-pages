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
            ->insertComposite('slug', 'cmd', array('folder'), '')
            ->insertInternal('folder', 'url')
            ->insertInternal('recurse', 'cmd')
            ->insertInternal('level', 'int', 0)
            ->insertInternal('collection', 'boolean');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'type'      => 'pages',
            'behaviors' => ['com://site/pages.model.behavior.recursable' => ['key' => 'folder']]
        ]);

        parent::_initialize($config);
    }

    public function fetchData()
    {
        if(!isset($this->__data))
        {
            $this->__data = array();

            //If folder is not defined to set root path
            $state  = $this->getState();
            $folder = $state->folder ?? '.';

            if($folder)
            {
                $registry = $this->getObject('page.registry');

                if ($state->isUnique())
                {
                    if($page = $registry->getPage($folder.'/'.$this->getState()->slug)) {
                        $this->__data = array($page->toArray());
                    }
                }
                else
                {
                    if(!$state->recurse) {
                        $mode = ComPagesPageRegistry::PAGES_ONLY;
                    } else {
                        $mode = ComPagesPageRegistry::PAGES_TREE;
                    }

                    $this->__data = array_values($registry->getPages($folder, $mode, $state->level - 1));
                }
            }
        }

        return $this->__data;
    }

    public function filterItem(&$page, KModelStateInterface $state)
    {
        $result = true;

        //Un-routable
        if($page['route'] === false) {
            $result = false;
        }

        //Collection
        if($result && !is_null($state->collection))
        {
            if($state->collection === true) {
                $result = isset($page['collection']) && $page['collection'] !== false;
            }

            if($state->collection === false) {
                $result = !isset($page['collection']) || $page['collection'] === false;
            }
        }

        //Permissions
        if($result)
        {
            //Goups
            if(isset($page['access']['groups']))
            {
                $groups = $this->getObject('com://site/pages.database.table.groups')
                    ->select($this->getObject('user')->getGroups(), KDatabase::FETCH_ARRAY_LIST);

                $groups = array_map('strtolower', array_column($groups, 'title'));

                if(!array_intersect($groups, $page['access']['groups'])) {
                    $result = false;
                }
            }

            //Roles
            if($result && isset($page['access']['roles']))
            {
                $roles = $this->getObject('com://site/pages.database.table.roles')
                    ->select($this->getObject('user')->getRoles(), KDatabase::FETCH_ARRAY_LIST);

                $roles = array_map('strtolower', array_column($roles, 'title'));

                if(!array_intersect($roles, $page['access']['roles'])) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    protected function _actionHash(KModelContext $context)
    {
        $data = array_column($context->data, 'hash', 'path');
        return hash('crc32b', serialize($data));
    }


    protected function _actionFetch(KModelContext $context)
    {
        foreach($context->data as $page)
        {
            //Unset page attributes
            unset($page['process']);
            unset($page['collection']);
            unset($page['form']);
            unset($page['layout']);
            unset($page['redirect']);
        }

        return parent::_actionFetch($context);
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }
}