<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtK2ModelEntityArticle extends ExtK2ModelEntityAbstract
{
	protected function _initialize(KObjectConfig $config)
	{
		$config->append([
			'data' => [
				'id'		  => '',
				'slug'        => '',
				'name'        => '',
				'title'       => '',
				'summary'     => '',
				'excerpt'     => '',
				'text'        => '',
				'category'    => '',
				'tags'        => '',
 				'date'           => 'now',
				'edited_date'    => '',
				'published_date' => '',
				'archived_date'  => '',
				'author'      => '',
				'editor'      => '',
				'image'       => [
					'url' 	   => '',
					'alt'	   => '',
					'caption'  => '',
				],
				'metadata'    => [
					'og:type'        => 'article',
					'og:title'       => null,
					'og:url'         => null,
					'og:image'       => null,
					'og:description' => null,

					//http://ogp.me/ns/article
					'article:published_time'  => null,
					'article:modified_time'   => null,
					'article:expiration_time' => null,
					'article:tag'             => [],
				],
				'fields'      => [],
				'parameters'  => [],
				'impressions' => 0,
				'direction'   => 'auto',
				'language'    => 'en-GB',
			],
			'internal_properties' => [ 'excerpt'],
		]);

		parent::_initialize($config);
	}

	public function getPropertyExcerpt()
	{
		$parts = preg_split('#<!--(.*)more(.*)-->#i', $this->getContent(), 2);

		if(count($parts) > 1) {
			$excerpt = $parts[0];
		} else {
			$excerpt = '';
		}

		return $excerpt;
	}

	public function getPropertyText()
	{
		$parts = preg_split('#<!--(.*)more(.*)-->#i', $this->getContent(), 2);

		if(count($parts) > 1) {
			$text = $parts[1];
		} else {
			$text = $parts[0];
		}

		return $text;
	}

	public function getPropertyImage()
	{
		//Normalize images
		$image = array();

		$hash = md5("Image".$this->id);
		$path = '/media/k2/items/src/'.$hash.'.jpg';

		if(file_exists(JPATH_ROOT.$path))
		{
			$url =  $this->getBasePath().$path;
			$url = $this->getObject('lib:http.url')->setUrl($url);

			$image = [
				'url'      => $url,
				'alt'      => '',
				'caption'  => $this->_image_caption ?? ''
			];
		}

		return new ComPagesObjectConfig($image);
	}

	public function getPropertyMetadata()
	{
		$metadata = parent::getPropertyMetadata();

		if(!empty($this->published_date)) {
			$metadata->set('article:published_time', (string) $this->published_date);
		}

		if(!empty($this->edited_date)) {
			$metadata->set('article:modified_time', (string) $this->edited_date);
		}

		if(!empty($this->archived_date)) {
			$metadata->set('article:expiration_time', (string) $this->archived_date);
		}

		if(count($this->tags)) {
			$metadata->set('article:tag', implode(',', $this->tags->toArray()));
		}

		return $metadata;
	}

	public function getPropertyAttachments()
	{
		//Get the single category
		$attachments = $this->getObject('ext:k2.model.attachments')
			->article($this->id)
			->fetch();

		return $attachments;
	}

	public function setPropertyTags($value)
	{
		if($value)
		{
			if(is_string($value)) {
				$value = explode(',', $value);
			}
		}
		else $value = [];

		return new ComPagesObjectConfig($value);
	}

	public function setPropertyCategory($value)
	{
		if($value)
		{
			//Get the single category
			$value = $this->getObject('ext:k2.model.categories')
				->id($value)
				->fetch()
				->find($value);
		}

		return $value;
	}

	public function setPropertyEditedDate($value)
	{
		$date = null;

		if($value && $value != '0000-00-00 00:00:00')
		{
			if(is_integer($value)) {
				$date = $this->getObject('date')->setTimestamp($value);
			} else {
				$date = $this->getObject('date', array('date' => trim($value)));
			}
		}

		return $date;
	}

	public function setPropertyPublishedDate($value)
	{
		$date = null;

		if($value && $value != '0000-00-00 00:00:00')
		{
			if(is_integer($value)) {
				$date = $this->getObject('date')->setTimestamp($value);
			} else {
				$date = $this->getObject('date', array('date' => trim($value)));
			}
		}

		return $date;
	}

	public function setPropertyArchivedDate($value)
	{
		$date = null;

		if($value && $value != '0000-00-00 00:00:00')
		{
			if(is_integer($value)) {
				$date = $this->getObject('date')->setTimestamp($value);
			} else {
				$date = $this->getObject('date', array('date' => trim($value)));
			}
		}

		return $date;
	}

	public function setPropertyFields($value)
	{
		if($value && is_string($value)) {
		 	$value = json_decode($value, true);
		}

		if($value && !empty($value))
		{
			//Get the single field
			$fields = $this->getObject('ext:k2.model.fields')
				->id(array_column($value, 'id'))
				->fetch();

			foreach($value as $v)
			{
				if($field = $fields->find($v['id']))
				{
					//Set the value
					$field->value = $v['value'];
				}
			}
		}
		else $fields = $this->getObject('ext:k2.model.fields')->create();

		return $fields;
	}

	public function setPropertyParameters($value)
	{
		if($value && is_string($value)) {
			$value = json_decode($value, true);
		}

		//$params = JComponentHelper::getParams('com_k2')->toArray();

		$config  = new ComPagesObjectConfig(/*$params*/);
		$config->merge($value); // Override global params with specific params

		return $config;
	}

	public function getEditor()
	{
		$user = $this->getObject('user.provider')->load($this->editor);
		return $user;
	}

	public function getImages()
	{
		$hash = md5("Image".$this->id);
		$path = '/media/k2/items/cache/'.$hash;

		$images = [
			'XS' => $path.'_XS.jpg',
			'S'  => $path.'_S.jpg',
			'M'  => $path.'_M.jpg',
			'L'  => $path.'_L.jpg',
			'XL' => $path.'_XL.jpg',
		];

		return $images;
	}
}
