<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaModelMenus extends ComPagesModelDatabase
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insertUnique('id', 'cmd')
            ->insertUnique('slug', 'cmd')

            ->insert('menutype', 'cmd')
            ->insert('published' , 'boolean')
            ->insert('language', 'cmd')
            ->insert('access' , 'cmd', array_unique($this->getObject('user')->getRoles()))
        ;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'persistable' => false,
            'type'    => 'menus',
            'entity'  => 'menu',
            'table'   => 'menu',
            'filters' => [
                'params' => 'json'
            ],
            'behaviors' => ['com:pages.model.behavior.recursable' => ['key' => 'parent']]
        ));

        parent::_initialize($config);
    }

    public function getQuery($columns = true)
    {
        $state = $this->getState();

        $query = $this->getObject('database.query.select')
            ->table(array('tbl' => $this->getTable()->getName()));

        //#__content
        if($columns)
        {
            $query->columns([
                'id'    => 'tbl.id',
                'title' => 'tbl.title',
                'name'  => 'tbl.title',
                'type'  => 'tbl.type',
                'menu'  => 'tbl.menutype',
                'slug'  => 'tbl.alias',
                'level' => 'tbl.level',

                'default' => 'tbl.home',
                'link'    => 'tbl.link',
                'path'    => 'tbl.path',

                'parent'    => 'IF(tbl.parent_id > 1, tbl.parent_id, NULL)',
                'published' => 'tbl.published',

                'parameters'  => 'tbl.params',
                'language'    => 'SUBSTRING_INDEX(tbl.language, "-", 1)',
                'component'   => 'SUBSTRING_INDEX(e.element, "_", -1)',

                //Protected properties (for getters)
            ]);
        }

        //Joins
        $query->join(['g' => 'usergroups'], 'tbl.access = g.id');
        $query->join(['e' => 'extensions'], 'tbl.component_id = e.extension_id');

        if(!is_null($state->id))
        {
            if(is_string($state->id)) {
                $menus = array_unique(explode(',',  $state->id));
            } else {
                $menus = (array) $state->id;
            }

            $query->where('(tbl.id IN :menus)')->bind(['menus' => $menus]);
        }
        else if(!is_null($state->slug)) {
            $query->where('(tbl.alias = :menu)')->bind(['menu' => $state->slug]);
        }

        if (!is_null($state->access))
        {
            if(is_string($state->access)) {
                $access = array_unique(explode(',',  $state->access));
            } else {
                $access = (array) $state->access;
            }

            //If user doesn't have access to the category, he doesn't have access to the articles
            $query->where('(tbl.access IN :access)')->bind(['access' => $access]);
        }

        if (!is_null($state->published))
        {
            if($state->published) {
                $query->where('(tbl.published = 1)');
            } else {
                $query->where('(tbl.published = 0)');
            }
        }

        if(!is_null($state->menutype))
        {
            if(is_string($state->menutype)) {
                $menutypes = array_unique(explode(',',  $state->menutype));
            } else {
                $menutypes = (array) $state->menutype;
            }
            $query->where('(tbl.menutype IN :menutype)')->bind(['menutype' => $menutypes]);
        }

        if (!is_null($state->language)) {
            $query->where('(SUBSTRING_INDEX(tbl.language, "-", 1) = :language)')->bind(['language' => $state->language]);
        } else {
            $query->where('tbl.language = :language')->bind(['language' => '*']);
        }

        if (!is_null($state->level)) {
            $query->where('tbl.level <= :level')->bind(['level' => (int) $state->level]);
        }

        //Only fetch content categories
        $query->where('tbl.client_id = :client')->bind(['client' => '0']);
        $query->where('tbl.parent_id > 0');
        $query->order('tbl.lft');

        return $query;
    }
}