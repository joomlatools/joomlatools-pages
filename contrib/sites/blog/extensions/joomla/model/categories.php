<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaModelCategories extends ComPagesModelDatabase
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insert('id'        , 'cmd', null, true)

            ->insert('published' , 'boolean')
            ->insert('language', 'cmd')

            ->insert('author' , 'string')
            ->insert('editor' , 'string')
            ->insert('access' , 'cmd', array_unique($this->getObject('user')->getRoles()))
        ;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'persistable' => false,
            'type'   => 'article_categories',
            'entity' => 'category',
            'table'  => 'categories',
        ));
        parent::_initialize($config);
    }

    public function fetchData($count = false)
    {
        $state = $this->getState();

        $query = $this->getObject('database.query.select')
            ->table(array('tbl' => $this->getTable()->getName()));

        //#__content
        if(!$count)
        {
            $query->columns([
                'id'       => 'tbl.id',
                'title'    => 'tbl.title',
                'slug'     => 'tbl.alias',
                'parent'   => 'IF(tbl.parent_id > 1, tbl.parent_id, NULL)',
                'summary'  => 'tbl.metadesc',
                'content'  => 'tbl.description',
                'published' => 'tbl.published',

                'author'      => 'tbl.created_user_id',
                'editor'      => 'GREATEST(tbl.created_user_id, tbl.modified_user_id)',

                'date'        => 'tbl.created_time',
                'edited_date' => 'GREATEST(tbl.created_time, tbl.modified_time)',

                'parameters'  => 'tbl.params',
                'impressions' => 'tbl.hits',
                'language'    => 'SUBSTRING_INDEX(tbl.language, "-", 1)',

                //Protected properties (for getters)
                '_metadata'   => 'tbl.metadata',
            ]);
        }
        else $query->columns('COUNT(*)');

        //Joins
        $query->join(['g' => 'usergroups'], 'tbl.access = g.id');

        if(!is_null($state->id))
        {
            if(is_string($state->id)) {
                $categories = array_unique(explode(',',  $state->id));
            } else {
                $categories = (array) $state->id;
            }

            $query->where('(tbl.id IN :categories)')->bind(['categories' => $categories]);
        }

        if(!is_null($state->author))
        {
            if(is_string($state->author)) {
                $users = array_unique(explode(',',  $state->author));
            } else {
                $users = (array) $state->author;
            }

            $query->where('(tbl.created_user_id IN :authors)')->bind(['authors' => $users]);
        }

        if (!is_null($state->editor))
        {
            if(is_string($state->editor)) {
                $users = array_unique(explode(',',  $state->editor));
            } else {
                $users = (array) $state->editor;
            }

            $query->where('(tbl.modified_user_id IN :editors)')->bind(['editors' => $users]);
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

        if (!is_null($state->language)) {
            $query->where('(SUBSTRING_INDEX(tbl.language, "-", 1) = :language)')->bind(['language' => $state->language]);
        } else {
            $query->where('tbl.language = :language')->bind(['language' => '*']);
        }

        //Only fetch content categories
        $query->where('tbl.extension = :component')->bind(['component' => 'com_content']);

        return $query;
    }

    public function getHash($refresh = false)
    {
        $query = $this->getObject('database.query.select')
            ->table(['tbl' => $this->getTable()->getName()])
            ->columns(['hash' => 'MAX(GREATEST(tbl.created_time, tbl.modified_time))']);

        if($modified = $this->getTable()->select($query, KDatabase::FETCH_FIELD)) {
            $hash = hash('crc32b', $modified);
        }

        return $hash;
    }
}