<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtK2ModelAttachments extends ComPagesModelDatabase
{
	public function __construct(KObjectConfig $config)
	{
		parent::__construct($config);

		$this->getState()
			->insert('id', 'cmd', null, true)
			->insert('article', 'int');
	}

	protected function _initialize(KObjectConfig $config)
	{
		$config->append(array(
			'persistable' => false,
			'type'   => 'article_attachments',
			'entity' => 'attachment',
			'table'  => 'k2_attachments',
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
				'id'      => 'tbl.id',
				'title'   => 'tbl.title',
				'url'     => 'tbl.filename',
				'alt'     => 'tbl.titleAttribute',
				'impressions' => 'tbl.hits',
			]);
		}
		else $query->columns('COUNT(*)');

		if(!is_null($state->id))
		{
			if(is_string($state->id)) {
				$articles = array_unique(explode(',',  $state->id));
			} else {
				$articles = (array) $state->id;
			}

			$query->where('(tbl.id IN :articles)')->bind(['articles' => $articles]);
		}

		if(!is_null($state->article))
		{
			if(is_string($state->article)) {
				$articles = array_unique(explode(',',  $state->article));
			} else {
				$articles = (array) $state->article;
			}

			$query->where('(tbl.itemID IN :articles)')->bind(['articles' => $articles]);
		}

		return $query;
	}
}
