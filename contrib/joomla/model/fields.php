<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaModelFields extends ComPagesModelDatabase
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insert('id', 'cmd', null, true)
            ->insert('group'     , 'int')
            ->insert('article'   , 'int')
            ->insert('published' , 'boolean');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'persistable' => false,
            'type'   => 'article_fields',
            'entity' => 'field',
            'table'  => 'fields',
        ));
        parent::_initialize($config);
    }

    public function fetchData($count = false)
    {
        $state = $this->getState();

        $query = $this->getObject('database.query.select')
            ->table(array('tbl' => $this->getTable()->getName()));

        if(!$count)
        {
            $query->columns([
                'id'        => 'tbl.id',
                'name'      => 'tbl.name',
                'title'     => 'tbl.title',
                'type'      => 'tbl.type',
                'value'     => 'v.value',
                'published' => 'IF(tbl.state = 1, 1, 0)',
            ]);
        }
        else $query->columns('COUNT(*)');

        //Joins
        $query
            ->join(['v' => 'fields_values'], 'tbl.id = v.field_id');

        if(!is_null($state->id))
        {
            if(is_string($state->id)) {
                $fields = array_unique(explode(',',  $state->id));
            } else {
                $fields = (array) $state->id;
            }

            $query->where('(tbl.id IN :fields)')->bind(['fields' => $fields]);
        }

        if(!is_null($state->article))
        {
            if(is_string($state->article)) {
                $articles = array_unique(explode(',',  $state->article));
            } else {
                $articles = (array) $state->article;
            }

            $query->where('(v.item_id IN :articles)')->bind(['articles' => $articles]);
        }

        if(!is_null($state->group))
        {
            if(is_string($state->group)) {
                $groups = array_unique(explode(',',  $state->group));
            } else {
                $groups = (array) $state->group;
            }

            $query->where('(tbl.group_id IN :groups)')->bind(['groups' => $groups]);
        }

        if (!is_null($state->published))
        {
            if($state->published) {
                $query->where('(tbl.state = 1)');
            } else {
                $query->where('(tbl.state = 0)');
            }
        }

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
