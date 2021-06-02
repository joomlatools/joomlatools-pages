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
            ->insert('group'     , 'int')
            ->insert('article'   , 'int')
            ->insert('published' , 'boolean')
            ->insert('language', 'cmd')
            ->insert('access' , 'cmd', array_unique($this->getObject('user')->getRoles()))
        ;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'persistable' => false,
            'type'   => 'article_fields',
            'entity' => 'field',
            'table'  => 'fields',
            'identity_key' => 'name',
        ));
        parent::_initialize($config);
    }

    public function getQuery($columns = true)
    {
        $state = $this->getState();

        //Increase group_concat limit to 4.096 for the SESSION
        $this->getTable()->getAdapter()->execute('SET SESSION group_concat_max_len = 4096');

        $query = $this->getObject('database.query.select')
            ->table(array('tbl' => $this->getTable()->getName()));

        if($columns)
        {
            $query->columns([
                'id'        => 'tbl.id',
                'name'      => 'tbl.name',
                'title'     => 'tbl.title',
                'type'      => 'tbl.type',
                'label'     => 'tbl.label',
                'default'   => 'tbl.default_value',

                'published' => 'IF(tbl.state = 1, 1, 0)',
                'required'  => 'tbl.required',
                'params'    => 'tbl.fieldparams',
                'group'     => 'g.title',
                'language'  => 'SUBSTRING_INDEX(tbl.language, "-", 1)',
                'multi'     => 'COUNT(*) > 1',

                //Protected properties (for getters)
                '_value'     => 'IF(COUNT(*) > 1, GROUP_CONCAT(v.value), v.value)',
            ]);
        }

        //Joins
        $query
            ->join(['v' => 'fields_values'], 'tbl.id = v.field_id')
            ->join(['g' => 'fields_groups'], 'tbl.group_id = g.id');

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

        if (!is_null($state->access))
        {
            if(is_string($state->access)) {
                $access = array_unique(explode(',',  $state->access));
            } else {
                $access = (array) $state->access;
            }

            $query->where('(tbl.access IN :access)')->bind(['access' => $access]);
        }

        if (!is_null($state->language)) {
            $query->where('(SUBSTRING_INDEX(tbl.language, "-", 1) = :language)')->bind(['language' => $state->language]);
        } else {
            $query->where('tbl.language = :language')->bind(['language' => '*']);
        }

        $query->group(['v.field_id', 'v.item_id']);

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