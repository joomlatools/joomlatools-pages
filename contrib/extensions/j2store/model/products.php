<?php
class ExtJ2StoreModelProducts extends ComPagesModelDatabase
{
	public function __construct(KObjectConfig $config)
	{
		parent::__construct($config);

		$this->getState()
			->insertUnique('id', 'cmd')
			->insertUnique('slug', 'cmd')
			->insert('category'  , 'cmd')
			->insert('tags'      , 'cmd')

			->insert('published' , 'boolean')
			->insert('archived'  , 'boolean')
			->insert('trashed'   , 'boolean', false)
			->insert('featured'  , 'boolean')
			->insert('visible'   , 'boolean')

			->insert('author' , 'string')
			->insert('editor' , 'string')
			->insert('access' , 'cmd', array_unique($this->getObject('user')->getRoles()))
		;
	}

	protected function _initialize(KObjectConfig $config)
	{
		$config->append(array(
			'persistable' => false,
			'type'    => 'products',
			'entity'  => 'product',
			'table'   => 'content',
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
			//#__tags
			$query->columns([
				'tags'	=> $this->getObject('database.query.select')
					->table(array('t' => 'tags'))
					->columns('GROUP_CONCAT(t.title)')
					->join(['m' => 'contentitem_tag_map'], 'm.tag_id = t.id')
					->where('m.content_item_id = tbl .id')
			]);		
	
			$query->columns([
				'id'       => 'p.j2store_product_id',
				'title'    => 'tbl.title',
				'slug'     => 'tbl.alias',
				'summary'  => 'tbl.metadesc',
				'content'  => 'CONCAT_WS("<!--more-->", tbl.introtext, IF(LENGTH(tbl.fulltext), tbl.fulltext ,NULL))',
				'category' => 'tbl.catid',

				'published' => 'IF(tbl.state = 1, 1, 0)',
				'archived'  => 'IF(tbl.state = 2, 1, 0)',
				'trashed'   => 'IF(tbl.state = -2, 1, 0)',
				'featured'  => 'tbl.featured',

				'author'      => 'p.created_by',
				'editor'      => 'GREATEST(p.created_by, p.modified_by)',

				'date'           => 'p.created_on',
				'edited_date'    => 'GREATEST(p.created_on, p.modified_on)',
				'published_date' => 'tbl.publish_up',
				'archived_date'  => 'tbl.publish_down',

				'image'       => 'tbl.images',
				'links'       => 'tbl.urls',
				'parameters'  => 'tbl.attribs',
				'impressions' => 'tbl.hits',

				'_product_type'     => 'p.product_type',
				'_product_tag'      => 'p.main_tag',
				'_product_visible'  => 'p.visibility',
				'_product_enabled'  => 'p.enabled',
				'_product_button'   => 'p.addtocart_text',
				'_product_price'    => 'v.price',
				'_product_button'   => 'p.addtocart_text',

				'_product_image_main'      => 'i.main_image',
				'_product_image_main_alt'  => 'i.main_image_alt',
				'_product_image_thumb'     => 'i.thumb_image',
				'_product_image_thumb_alt' => 'i.thumb_image_alt',

				//Protected properties (for getters)
				'_metadata'   => 'tbl.metadata',
			]);
		}

		//Joins
		$query
			->join(['c' => 'categories']           , 'tbl.catid = c.id')
			->join(['g' => 'usergroups']           , 'tbl.access = g.id')
			->join(['m' => 'contentitem_tag_map']  , 'tbl.id = m.content_item_id')
			->join(['t' => 'tags']				   , 't.id = m.tag_id')
			->join(['p' => 'j2store_products']	   , 'p.product_source_id = tbl.id')
			->join(['v' => 'j2store_variants']	   , 'v.product_id = p.j2store_product_id')
			->join(['i' => 'j2store_productimages'], 'i.product_id = p.j2store_product_id');

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

		if (!is_null($state->visible)) {
			$query->where('(p.visibility = :visible)')->bind(['visible' => (bool) $state->visible]);
		}

		//Only fetch content categories
		$query->where('p.product_source = :component')->bind(['component' => 'com_content']);

		return $query;
	}
}
