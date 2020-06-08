<?php
class ExtK2ModelCategories extends ComPagesModelDatabase
{
	public function __construct(KObjectConfig $config)
	{
		parent::__construct($config);

		$this->getState()
			->insert('id'        , 'cmd', null, true)
			->insert('published' , 'bool')
			->insert('access'    , 'cmd', array_unique($this->getObject('user')->getRoles()))
		;
	}

	protected function _initialize(KObjectConfig $config)
	{
		$config->append(array(
			'persistable' => false,
			'type'   => 'article_categories',
			'entity' => 'category',
			'table'  => 'k2_categories',
		));
		parent::_initialize($config);
	}

	public function fetchData($count = false)
	{
		$state = $this->getState();

		$query = $this->getObject('database.query.select')
			->table(array('tbl' => $this->getTable()->getName()));

		//#__k2_categories
		if(!$count)
		{
			$query->columns([
				'id'       => 'tbl.id',
				'title'    => 'tbl.name',
				'slug'     => 'tbl.alias',
				'parent'   => 'IF(tbl.parent > 0, tbl.parent, NULL)',
				'content'  => 'tbl.description',
				'image'    => 'tbl.image',

				'published' => 'tbl.published',

				'parameters' => 'tbl.params',
			]);
		}
		else $query->columns('COUNT(*)');

		if(!is_null($state->id))
		{
			if(is_string($state->id)) {
				$categories = array_unique(explode(',',  $state->id));
			} else {
				$categories = (array) $state->id;
			}

			$query->where('(tbl.id IN :categories)')->bind(['categories' => $categories]);
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
				$query->where('(tbl.published = 0');
			}
		}

		return $query;
	}
}
