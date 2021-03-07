<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtK2ModelArticles extends ComPagesModelDatabase
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insertUnique('id'  , 'cmd')
            ->insertUnique('slug', 'cmd')
            ->insert('category'  , 'cmd')
            ->insert('tags'      , 'cmd')

            ->insert('published' , 'boolean')
            ->insert('archived'  , 'boolean')
            ->insert('trashed'   , 'boolean', false)
            ->insert('featured'  , 'boolean')

            ->insert('author' , 'string')
            ->insert('editor' , 'string')
            ->insert('access' , 'cmd', array_unique($this->getObject('user')->getRoles()))
        ;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'persistable' => false,
            'type'    => 'articles',
            'entity'  => 'article',
            'table'   => 'k2_items',
            'aliases' => array()
        ));
        parent::_initialize($config);
    }

    public function getAliases()
    {
        return $this->getConfig()->aliases;
    }

    public function getQuery($columns = true)
    {
        $state = $this->getState();

        $query = $this->getObject('database.query.select')
            ->table(array('tbl' => $this->getTable()->getName()));

        //#__content
        if($columns)
        {
            //#__tags
            $query->columns([
                'tags'	=> $this->getObject('database.query.select')
                    ->table(array('t' => 'k2_tags'))
                    ->columns('GROUP_CONCAT(t.name)')
                    ->join(['m' => 'k2_tags_xref'], 'm.tagID = t.id')
                    ->where('m.itemID = tbl.id')
                    ->where('(t.published = :published)')->bind(['published' => 1])
            ]);


            $query->columns([
                'id'       => 'tbl.id',
                'title'    => 'tbl.title',
                'slug'     => 'tbl.alias',
                'summary'  => 'tbl.metadesc',
                'content'  => 'CONCAT_WS("<!--more-->", tbl.introtext, IF(LENGTH(tbl.fulltext), tbl.fulltext ,NULL))',
                'category' => 'tbl.catid',

                'published' => 'tbl.published',
                'archived'  => 'IF(tbl.publish_down > CURRENT_TIMESTAMP, 1, 0)',
                'trashed'   => 'tbl.trash',
                'featured'  => 'tbl.featured',

                'author'      => 'tbl.created_by',
                'editor'      => 'GREATEST(tbl.created_by, tbl.modified_by)',

                'date'           => 'tbl.created',
                'edited_date'    => 'GREATEST(tbl.created, tbl.modified)',
                'published_date' => 'tbl.publish_up',
                'archived_date'  => 'tbl.publish_down',

                'fields'      => 'tbl.extra_fields',
                'parameters'  => 'tbl.params',
                'impressions' => 'tbl.hits',

                //Protected properties (for getters)
                '_metadata'      => 'tbl.metadata',
                '_image_caption' => 'tbl.image_caption',
            ]);
        }
        else $query->columns('COUNT(*)');

        //Joins
        $query
            ->join(['c' => 'k2_categories'] , 'tbl.catid = c.id')
            ->join(['g' => 'usergroups']    , 'tbl.access = g.id')
            ->join(['m' => 'k2_tags_xref']  , 'tbl.id = m.itemID')
            ->join(['t' => 'k2_tags']		, 't.id = m.tagID');

        if(!is_null($state->id))
        {
            if(is_string($state->id)) {
                $articles = array_unique(explode(',',  $state->id));
            } else {
                $articles = (array) $state->id;
            }

            $query->where('(tbl.id IN :articles)')->bind(['articles' => $articles]);
        }
        else if(!is_null($state->slug)) {
            $query->where('(tbl.alias = :article)')->bind(['article' => $state->slug]);
        }

        if(!is_null($state->category))
        {
            if(is_string($state->category)) {
                $categories = array_unique(explode(',',  $state->category));
            } else {
                $categories = (array) $state->category;
            }

            $query->where('(tbl.catid IN :category)')->bind(['category' => $categories]);
        }

        if(!is_null($state->tags))
        {
            if(is_string($state->tags)) {
                $tags = array_unique(explode(',',  $state->tags));
            } else {
                $tags = (array) $state->tags;
            }

            $query->where('(t.title IN :tags)')->bind(['tags' => $tags]);
        }

        if(!is_null($state->author))
        {
            if(is_string($state->author)) {
                $users = array_unique(explode(',',  $state->author));
            } else {
                $users = (array) $state->author;
            }

            $query->where('(tbl.created_by IN :authors)')->bind(['authors' => $users]);
        }

        if (!is_null($state->editor))
        {
            if(is_string($state->editor)) {
                $users = array_unique(explode(',',  $state->editor));
            } else {
                $users = (array) $state->editor;
            }

            $query->where('(tbl.modified_by IN :editors)')->bind(['editors' => $users]);
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
            $query->where('(c.access IN :access)')->bind(['access' => $access]);
        }

        if (!is_null($state->published))
        {
            if($state->published) {
                $query->where('(tbl.published = 1)');
            } else {
                $query->where('(tbl.published = 0)');
            }
        }

        if (!is_null($state->trashed))
        {
            if($state->trashed) {
                $query->where('(tbl.trash = 1)');
            } else {
                $query->where('(tbl.trash = 0)');
            }
        }

        if (!is_null($state->archived))
        {
            if($state->archived) {
                $query->where('(tbl.publish_down > CURRENT_TIMESTAMP)');
            } else {
                $query->where('(tbl.publish_down < CURRENT_TIMESTAMP)');
            }
        }

        if (!is_null($state->featured)) {
            $query->where('(tbl.featured = :featured)')->bind(['featured' => (bool) $state->featured]);
        }

        return $query;
    }
}
