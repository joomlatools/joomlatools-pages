<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');

class ExtJoomlaModelEntityArticle extends ExtJoomlaModelEntityAbstract
{
	protected $_images = [];

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
				'intro' => [
					'image' => ['url' => '', 'alt' => '', 'caption' => ''],
				],
				'full' => [
					'image' => ['url' => '', 'alt' => '', 'caption' => ''],
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

	public function getPropertyMetadata()
	{
		$metadata = parent::getPropertyMetadata();

		// Remove xreference
		unset($metadata['xreference']);

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

	public function getPropertyFields()
	{
		$fields = $this->getObject('ext:joomla.model.fields')
						->article($this->id)
						->fetch();

		return $fields;
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
			$value = $this->getObject('ext:joomla.model.categories')
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

	public function setPropertyImage($value)
	{
		if($value && is_string($value)) {
			$value = json_decode($value, true);
		}

		$this->_images = is_array($value) ? $value : [];

		//Normalize images — fulltext takes priority, intro is fallback
		$image = array();

		if(isset($value['image_fulltext']) && $value['image_fulltext'])
		{
			$image = [
				'url'     => $this->_normalizeImageUrl($value['image_fulltext']),
				'alt'     => $value['image_fulltext_alt'] ?? '',
				'caption' => $value['image_fulltext_caption'] ?? '',
			];
		}
		elseif(isset($value['image_intro']) && $value['image_intro'])
		{
			$image = [
				'url'     => $this->_normalizeImageUrl($value['image_intro']),
				'alt'     => $value['image_intro_alt'] ?? '',
				'caption' => $value['image_intro_caption'] ?? '',
			];
		}

		return new ComPagesObjectConfig($image);
	}

	public function getPropertyIntro()
	{
		$image = ['url' => '', 'alt' => '', 'caption' => ''];

		if (!empty($this->_images['image_intro'])) {
			$image = [
				'url'     => $this->_normalizeImageUrl($this->_images['image_intro']),
				'alt'     => $this->_images['image_intro_alt'] ?? '',
				'caption' => $this->_images['image_intro_caption'] ?? '',
			];
		}

		return new ComPagesObjectConfig(['image' => $image]);
	}

	public function getPropertyFull()
	{
		$image = ['url' => '', 'alt' => '', 'caption' => ''];

		if (!empty($this->_images['image_fulltext'])) {
			$image = [
				'url'     => $this->_normalizeImageUrl($this->_images['image_fulltext']),
				'alt'     => $this->_images['image_fulltext_alt'] ?? '',
				'caption' => $this->_images['image_fulltext_caption'] ?? '',
			];
		}

		return new ComPagesObjectConfig(['image' => $image]);
	}

	private function _normalizeImageUrl($url)
	{
		if (is_string($url)) {
			// Pages was built for Joomla 3. Joomla 4+ media manager appends a
			// #joomlaImage://... fragment to store image dimensions. Use the
			// Joomla-native helper to strip it cleanly.
			$url = \Joomla\CMS\HTML\HTMLHelper::cleanImageURL($url)->url;
		}

		if (is_string($url) && strpos($url, '://') === false) {
			// Joomla article images live at JPATH_ROOT/images, which is a different
			// root from the Pages site images folder. The Pages image helper cannot
			// resolve JPATH_ROOT/images paths (its base_path points to site images).
			// Produce an absolute URL instead — supported() returns false for absolute
			// URLs without a configured origin, so the filter outputs a plain <img>.
			$baseUrl = rtrim((string) $this->getObject('request')->getBaseUrl(), '/');
			$url     = $baseUrl . '/' . ltrim($url, '/');
		}

		return $this->getObject('lib:http.url')->setUrl($url);
	}

	public function setPropertyLinks($value)
	{
		if($value && is_string($value)) {
			$value = array_filter(json_decode($value, true));
		}

		$links = array();
		foreach(['a', 'b', 'c'] as $name)
		{
			if(isset($value['url'.$name]))
			{
				$links[] = [
					'url'  => $value['url'.$name],
					'text' => $value['url'.$name.'text'] ?? '',
				];
			}
		}

		return new ComPagesObjectConfig($links);
	}

	public function setPropertyParameters($value)
	{
		if($value && is_string($value)) {
			$value = json_decode($value, true);
		}

		//$params = JComponentHelper::getParams('com_content')->toArray();

		$config  = new ComPagesObjectConfig(/*$params*/);
		$config->merge($value); // Override global params with specific params

		return $config;
	}

	public function getEditor()
	{
		$user = $this->getObject('user.provider')->load($this->editor);
		return $user;
	}

	public function getRoute()
	{
	    return JRoute::_(ContentHelperRoute::getArticleRoute($this->id.':'.$this->slug, $this->category->id));
	}
}
