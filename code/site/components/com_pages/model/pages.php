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
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);
        $this->getState()
            ->insert('path', 'url')
            ->insert('slug', 'cmd', '', true, array('path'))
            //Internal states
            ->insert('recurse', 'boolean', true, false, array(), true)
            ->insert('level', 'int', 0, false, array(), true)
            ->insert('collection', 'boolean', null, false, array(), true)
            ->insert('format', 'word', null, false, array(), true)
            //Filter states
            ->insert('visible', 'boolean')
            ->insert('category', 'cmd')
            ->insert('year', 'int')
            ->insert('month', 'int')
            ->insert('day', 'int')
            ->insert('published', 'boolean');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors' => ['com:pages.model.behavior.recursable']
        ]);

        parent::_initialize($config);
    }


    public function getData($count = false)
    {
        $pages = array();
        $state = $this->getState();

        //Make sure we have a valid path
        if($path = $state->path)
        {
            $registry = $this->getObject('page.registry');

            if ($state->isUnique())
            {
                if($page = $registry->getPage($path.'/'.$this->getState()->slug)) {
                    $pages = array($page->toArray());
                }
            }
            else
            {
                if($state->recurse) {
                    $mode = ComPagesPageRegistry::PAGES_ONLY;
                } else {
                    $mode = ComPagesPageRegistry::PAGES_TREE;
                }


                $pages = array_values($registry->getPages($path, $mode, $state->level - 1));

                //Filter the pages
                $pages = $this->filterData($pages);
            }
        }

        return $pages;
    }

    public function filterItem($page, KModelStateInterface $state)
    {
        $result = true;

        //Un-routable
        if($page['route'] === false) {
            $result = false;
        }

        //Format
        if($result && !is_null($state->format)) {
            $result = isset($page['format']) && $page['format'] == $state->format;
        }

        //Visible
        if($result && !is_null($state->visible))
        {
            if($state->visible === true) {
                $result = !isset($page['visible']) || $page['visible'] !== false;
            }

            if($state->visible === false) {
                $result = isset($page['visible']) && $page['visible'] === false;
            }
        }

        //Published
        if($result &&  !is_null($state->published))
        {
            if($state->published === true) {
                $result = !isset($page['published']) || $page['published'] !== false;
            }

            if($state->published === false) {
                $result = isset($page['published']) && $page['published'] === false;
            }
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

        //Category
        if($result && (bool) $state->category) {
            $result =  isset($page['category']) && $page['category'] == $state->category;
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
        if($result)
        {
            //Goups
            if(isset($page['access']['groups']))
            {
                $groups = $this->getObject('com:pages.database.table.groups')
                    ->select($this->getObject('user')->getGroups(), KDatabase::FETCH_ARRAY_LIST);

                $groups = array_map('strtolower', array_column($groups, 'title'));

                if(!array_intersect($groups, $page['access']['groups'])) {
                    $result = false;
                }
            }

            //Roles
            if($result && isset($page['access']['roles']))
            {
                $roles = $this->getObject('com:pages.database.table.roles')
                    ->select($this->getObject('user')->getRoles(), KDatabase::FETCH_ARRAY_LIST);

                $roles = array_map('strtolower', array_column($roles, 'title'));

                if(!array_intersect($roles, $page['access']['roles'])) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}