<?php
class ExtK2ModelFields extends ComPagesModelDatabase
{
	public function __construct(KObjectConfig $config)
	{
		parent::__construct($config);

		$this->getState()
			->insert('id', 'cmd', null, true)
			->insert('group'     , 'int')
			->insert('published' , 'bool');
	}

	protected function _initialize(KObjectConfig $config)
	{
		$config->append(array(
			'persistable' => false,
			'type'   => 'article_fields',
			'entity' => 'field',
			'table'  => 'k2_extra_fields',
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
				'title'     => 'tbl.name',
				'type'      => 'tbl.type',
				'published' => 'tbl.published',

				//Protected properties (for getters)
				'_value'     => 'tbl.value',
			]);
		}
		else $query->columns('COUNT(*)');

		if(!is_null($state->id))
		{
			if(is_string($state->id)) {
				$fields = array_unique(explode(',',  $state->id));
			} else {
				$fields = (array) $state->id;
			}

			$query->where('(tbl.id IN :fields)')->bind(['fields' => $fields]);
		}

		if(!is_null($state->group))
		{
			if(is_string($state->group)) {
				$groups = array_unique(explode(',',  $state->group));
			} else {
				$groups = (array) $state->group;
			}

			$query->where('(tbl.group IN :groups)')->bind(['groups' => $groups]);
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
