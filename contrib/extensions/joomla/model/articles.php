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
            ->insertUnique('id'  , 'cmd')
            ->insertUnique('slug', 'cmd')
            ->insert('category'  , 'cmd')
            ->insert('tags'      , 'cmd')
            ->insert('field'     , 'cmd')

            ->insert('published' , 'boolean')
            ->insert('archived'  , 'boolean')
            ->insert('trashed'   , 'boolean', false)
            ->insert('featured'  , 'boolean')
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
                    ->table(['t' => 'tags'])
                    ->columns('GROUP_CONCAT(t.title)')
                    ->join(['m' => 'contentitem_tag_map'], 'm.tag_id = t.id')
                    ->where('m.content_item_id = tbl .id')
            ]);

            //#__fields
            /*$query->columns([
                'fields'	=> $this->getObject('database.query.select')
                    ->table(array('f' => 'fields'))
                     //->columns('GROUP_CONCAT(CONCAT_WS("=",f.name, v.value))')
                     ->columns('JSON_OBJECTAGG(f.name, v.value)')
                    ->join(['v' => 'fields_values'], 'v.field_id = f.id')
                    ->where('v.item_id = tbl.id')
            ]);*/

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
                'language'    => 'SUBSTRING_INDEX(tbl.language, "-", 1)',

                //Protected properties (for getters)
                '_metadata'     => 'tbl.metadata',
            ]);
        }

        //Joins
        $query
            ->join(['c' => 'categories'], 'tbl.catid = c.id')
            ->join(['g' => 'usergroups'], 'tbl.access = g.id');

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
            $id    = array();
            $alias = array();

            if (is_string($state->category)) {
                $categories = array_unique(explode(',', $state->category));
            } else {
                $categories = (array)$state->category;
            }

            foreach ($categories as $category)
            {
                if(is_numeric($category)) {
                    $id[]  = $category;
                } else {
                    $alias[] = $category;
                }
            }

            if($id) {
                $query->where('(tbl.catid IN :category)')->bind(['category' => $categories]);
            }

            if($alias) {
                $query->where('(c.alias IN :category)')->bind(['category' => $categories]);
            }
        }

        if(!is_null($state->tags))
        {
            $tags = (array) $state->tags;

            foreach($tags as $key => $tag)
            {
                $select = $this->getObject('database.query.select')
                    ->columns(['m.content_item_id'])
                    ->table(['t' => 'tags'])
                    ->join(['m' => 'contentitem_tag_map'], 't.id = m.tag_id');

                //AND - check if all tags exists
                if(is_array($tag))
                {
                    $tag = array_unique((array) $tag);

                    $select
                        ->where('(t.title IN :tag)')->bind(['tag' => $tag])
                        ->group(['m.content_item_id'])
                        ->having('COUNT(*) = :count')->bind(['count' => count($tag)]);
                }
                //OR - check if a tag exists
                else
                {
                    $tag = array_unique(array_map('trim', explode(',',  $tag)));

                    $select
                        ->distinct()
                        ->where('(t.title IN :tag)')->bind(['tag' => $tag]);
                }

                $key = 'tag_'.hash('crc32b', $key);
                $query->where('(tbl.id IN :'.$key.')')->bind([$key => $select]);
            }
        }

        if(!is_null($state->field) && is_array($state->field))
        {
            $query->join(['v' => 'fields_values'], 'tbl.id = v.item_id', 'INNER');
            $query->join(['f' => 'fields'], 'f.id = v.field_id', 'INNER');

            foreach($state->field as $name => $field)
            {
                $field = (array) $field;

                foreach($field as $key => $value)
                {
                    $select = $this->getObject('database.query.select')

                        ->columns(['v.item_id'])
                        ->table(['v' => 'fields_values'])
                        ->join(['f' => 'fields'], 'f.id = v.field_id');

                    //AND - check if all field values exists
                    if(is_array($value))
                    {
                        $value = array_unique((array) $value);

                        $select
                            ->where('f.name = :name')->bind(['name' => $name])
                            ->where('v.value IN :value')->bind(['value' => $value])
                            ->group(['v.item_id'])
                            ->having('COUNT(*) = :count')->bind(['count' => count($value)]);
                    }
                    //OR - check if a field value exists
                    else
                    {
                        $value = array_unique(array_map('trim', explode(',',  $value)));

                        $select
                            ->where('f.name = :name')->bind(['name' => $name])
                            ->where('v.value IN :value')->bind(['value' => $value]);

                    }

                    $key = 'field_'.hash('crc32b', $key.$name);
                    $query->where('(tbl.id IN :'.$key.')')->bind([$key => $select]);
                }
            }

            $query->group('tbl.id');
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

        if (!is_null($state->language)) {
            $query->where('(SUBSTRING_INDEX(tbl.language, "-", 1) = :language)')->bind(['language' => $state->language]);
        } else {
            $query->where('tbl.language = :language')->bind(['language' => '*']);
        }

        return $query;
    }

    public function _actionHash(KModelContext $context)
    {
        $hash = 1;

        $query = $this->getQuery(false);
        $query->columns(['hash' => 'MAX(GREATEST(tbl.created, tbl.modified))']);

        if($modified = $this->getTable()->select($query, KDatabase::FETCH_FIELD)) {
            $hash = hash('crc32b', $modified);
        }

        return $hash;
    }
}