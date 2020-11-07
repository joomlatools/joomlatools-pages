<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaModelArticles extends ComPagesModelDatabase
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insert('id'        , 'cmd', null, true)
            ->insert('slug'      , 'cmd', null, true)
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
            'table'   => 'content',
            'aliases' => array()
        ));

        parent::_initialize($config);
    }

    public function getAliases()
    {
        return $this->getConfig()->aliases;
    }

    public function fetchData($count = false)
    {
        $state = $this->getState();

        $query = $this->getObject('database.query.select')
            ->table(array('tbl' => $this->getTable()->getName()));


        //#__content
        if(!$count)
        {
            //#__tags
            $query->columns([
               'tags'	=> $this->getObject('database.query.select')
                    ->table(array('t' => 'tags'))
                    ->columns('GROUP_CONCAT(t.title)')
                    ->join(['m' => 'contentitem_tag_map'], 'm.tag_id = t.id')
                    ->where('m.content_item_id = tbl .id')
            ]);

            $query->columns([
                'id'       => 'tbl.id',
                'title'    => 'tbl.title',
                'slug'     => 'tbl.alias',
                'summary'  => 'tbl.metadesc',
                'content'  => 'CONCAT_WS("<!--more-->", tbl.introtext, IF(LENGTH(tbl.fulltext), tbl.fulltext ,NULL))',
                'category' => 'tbl.catid',

                'published' => 'IF(tbl.state = 1, 1, 0)',
                'archived'  => 'IF(tbl.state = 2, 1, 0)',
                'trashed'   => 'IF(tbl.state = -2, 1, 0)',
                'featured'  => 'tbl.featured',

                'author'      => 'tbl.created_by',
                'editor'      => 'GREATEST(tbl.created_by, tbl.modified_by)',

                'date'           => 'tbl.created',
                'edited_date'    => 'GREATEST(tbl.created, tbl.modified)',
                'published_date' => 'tbl.publish_up',
                'archived_date'  => 'tbl.publish_down',

                'image'       => 'tbl.images',
                'links'       => 'tbl.urls',
                'parameters'  => 'tbl.attribs',
                'impressions' => 'tbl.hits',

                //Protected properties (for getters)
                '_metadata'   => 'tbl.metadata',
            ]);
        }
        else $query->columns('COUNT(*)');

        //Joins
        $query
            ->join(['c' => 'categories']         , 'tbl.catid = c.id')
            ->join(['g' => 'usergroups']         , 'tbl.access = g.id')
            ->join(['m' => 'contentitem_tag_map'], 'tbl.id = m.content_item_id')
            ->join(['t' => 'tags']				 , 't.id = m.tag_id');

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
                $query->where('(tbl.state = 1)');
            } else {
                $query->where('(tbl.state = 0');
            }
        }

        if (!is_null($state->archived))
        {
            if($state->archived) {
                $query->where('(tbl.state = 2)');
            } else {
                $query->where('(tbl.state <> 2)');
            }
        }

        if (!is_null($state->trashed))
        {
            if($state->trashed) {
                $query->where('(tbl.state = -2)');
            } else {
                $query->where('(tbl.state <> -2)');
            }
        }

        if (!is_null($state->featured)) {
            $query->where('(tbl.featured = :featured)')->bind(['featured' => (bool) $state->featured]);
        }

        return $query;
    }

    public function getHash($refresh = false)
    {
        $query = $this->getObject('database.query.select')
            ->table(['tbl' => $this->getTable()->getName()])
            ->columns(['hash' => 'MAX(GREATEST(tbl.created, tbl.modified))']);

        if($modified = $this->getTable()->select($query, KDatabase::FETCH_FIELD)) {
            $hash = hash('crc32b', $modified);
        }

        return $hash;
    }
}
