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
            ->insert('path', 'url', '.')
            ->insert('slug', 'cmd', '', true, array('path'))
            //Internal states
            ->insert('recurse', 'boolean', true, false, array(), true)
            ->insert('level', 'int', 0, false, array(), true)
            //Filter states
            ->insert('visible', 'boolean')
            ->insert('collection', 'boolean')
            ->insert('sitemap', 'boolean')
            ->insert('category', 'cmd')
            ->insert('year', 'int')
            ->insert('month', 'int')
            ->insert('day', 'int')
            ->insert('published', 'boolean');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key' => 'path',
            'behaviors'    => [
                'recursable',
            ]
        ]);

        parent::_initialize($config);
    }

    public function getData()
    {
        if(!isset($this->_data))
        {
            $state    = $this->getState();
            $registry = $this->getObject('page.registry');

            //Make sure we have a valid path
            $pages = array();
            if($path = $state->path)
            {
                if ($this->getState()->isUnique())
                {
                    if($page = $registry->getPage($path.'/'.$this->getState()->slug)) {
                        $pages = $page->toArray();
                    }
                }
                else $pages = array_values($registry->getPages($path, $state->recurse, $state->level - 1));
            }

            $this->_data = $pages;
        }

        return $this->_data;
    }

    public function filterData($item, KModelStateInterface $state)
    {
        $result = true;

        //Visible
        if(!is_null($state->visible))
        {
            if($state->visible === true) {
                $result = !isset($item['visible']) || $item['visible'] !== false;
            }

            if($state->visible === false) {
                $result = isset($item['visible']) && $item['visible'] === false;
            }
        }

        //Published
        if($result &&  !is_null($state->published))
        {
            if($state->published === true) {
                $result = !isset($item['published']) || $item['published'] !== false;
            }

            if($state->published === false) {
                $result = isset($item['published']) && $item['published'] === false;
            }
        }

        //Collection
        if($result && !is_null($state->collection))
        {
            if($state->collection === true) {
                $result = isset($item['collection']) && $item['collection'] !== false;
            }

            if($state->collection === false) {
                $result = !isset($item['collection']) || $item['collection'] === false;
            }
        }

        //Sitemap
        if($result && (bool) $state->sitemap) {
            $result = (isset($item['sitemap']) && $item['sitemap'] == false) ? false : true;
        }

        //Category
        if($result && (bool) $state->category) {
            $result =  isset($item['category']) && $item['category'] == $state->category;
        }

        //Date
        if($result &&  (bool) ($state->year || $state->month || $state->day))
        {
            if(isset($item['date']))
            {
                //Get the timestamp
                if(!is_integer($item['date'])) {
                    $date = strtotime($item['date']);
                } else {
                    $date = $item['date'];
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
            if(isset($item['access']['groups']))
            {
                $groups = $this->getObject('com:pages.database.table.groups')
                    ->select($this->getObject('user')->getGroups(), KDatabase::FETCH_ARRAY_LIST);

                $groups = array_map('strtolower', array_column($groups, 'title'));

                if(!array_intersect($groups, $item['access']['groups'])) {
                    $result = false;
                }
            }

            //Roles
            if($result && isset($item['access']['roles']))
            {
                $roles = $this->getObject('com:pages.database.table.roles')
                    ->select($this->getObject('user')->getRoles(), KDatabase::FETCH_ARRAY_LIST);

                $roles = array_map('strtolower', array_column($roles, 'title'));

                if(!array_intersect($roles, $item['access']['roles'])) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}