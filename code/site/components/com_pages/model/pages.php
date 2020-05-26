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
            ->insert('folder', 'url')
            ->insert('slug', 'cmd', '', true, array('folder'))
            //Internal states
            ->insert('recurse', 'cmd', null, false, array(), true)
            ->insert('level', 'int', 0, false, array(), true)
            ->insert('collection', 'boolean', null, false, array(), true)
            //Filter states
            ->insert('year', 'int')
            ->insert('month', 'int')
            ->insert('day', 'int');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'type'      => 'pages',
            'behaviors' => ['com://site/pages.model.behavior.recursable' => ['key' => 'folder']]
        ]);

        parent::_initialize($config);
    }

    public function getHash()
    {
        return $this->getObject('page.registry')->getHash();
    }

    public function fetchData($count = false)
    {
        if(!isset($this->__data))
        {
            $this->__data = array();
            $state       = $this->getState();

            //Set the folder to the active page path if no folder is defined
            if($state->folder === null) {
                $folder = $this->getPage()->path;
            } else {
                $folder = $state->folder;
            }

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

    public function filterItem($page, KModelStateInterface $state)
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

        //Date
        if($result &&  (bool) ($state->year || $state->month || $state->day))
        {
            if(isset($page['date']))
            {
                //Get the timestamp
                if(!is_integer($page['date'])) {
                    $date = strtotime($page['date']);
                } else {
                    $date = $page['date'];
                }

                if($state->year) {
                    $result = ($state->year == date('Y', $date));
                }

                if($result && $state->month) {
                    $result = ($state->month == date('m', $date));
                }

                if($result && $state->day) {
                    $result = ($state->day == date('d', $date));
                }
            }
        }

        //Permissions
        if($result) {
            $result = $this->getObject('page.registry')->isPageAccessible($page['path']);
        }

        return $result;
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }
}