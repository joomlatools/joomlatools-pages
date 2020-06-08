<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ExtJoomlaModelEntityAbstract extends ComPagesModelEntityItem
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
				'text'        => '',
 				'date'           => 'now',
				'author'      => '',
				'image'       => [
					'url' 	   => '',
					'alt'	   => '',
					'caption'  => '',
				],
				'metadata'    => [
					'og:type'        => 'website',
					'og:title'       => null,
					'og:url'         => null,
					'og:image'       => null,
					'og:description' => null,
				],
				'parameters'  => [],
				'direction'   => 'auto',
				'language'    => 'en-GB',
			],
			'internal_properties' => ['content', 'text', 'parameters'],
			'base_path'           => $this->getObject('request')->getBasePath(),
		]);

		parent::_initialize($config);
	}

	public function get($property, $default = null)
	{
		if($this->hasProperty($property)) {
			$result = $this->getProperty($property);
		} else {
			$result = $default;
		}

		return $result;
	}

	public function getPropertyName()
	{
		if(empty($name)) {
			$name = ucwords(str_replace(array('_', '-'), ' ', $this->slug));
		}

		return $name;
	}

	public function getPropertyText()
	{
		return $this->getContent();
	}

	public function getPropertyMetadata()
	{
		//Get the raw metadata
		$metadata = $this->_metadata;

		if(is_string($metadata)) {
			$metadata = array_filter((array) json_decode($metadata, true));
		}

		//Remove empty values
		$metadata = new KObjectConfigJson($metadata);
		$metadata->append($this->getConfig()->data->metadata);

		if(!isset($metadata->description) && $this->summary) {
			$metadata->set('description', $this->summary);
		}

		//Only set one image (give priority to the text image)
		if($this->image && $this->image->url) {
			$metadata->set('og:image', $this->image->url);
		}

		//Type and image are required. If they are not set remove any opengraph properties
		if(!empty($metadata->get('og:type')) && $metadata->has('og:image'))
		{
			if($this->title) {
				$metadata->set('og:title', $this->title);
			}

			if($this->summary) {
				$metadata->set('og:description', $this->summary);
			}

			if($this->language) {
				$metadata->set('og:locale', $this->language);
			}
		}
		else
		{
			foreach($metadata as $name => $value)
			{
				if(strpos($name, 'og:') === 0 || strpos($name, 'twitter:') === 0) {
					$metadata->remove($name);
				}
			}
		}

		return $metadata;
	}

	public function setPropertyDate($value)
	{
		if(is_integer($value)) {
			$date = $this->getObject('date')->setTimestamp($value);
		} else {
			$date = $this->getObject('date', array('date' => trim($value)));
		}

		return $date;
	}

	public function setPropertyParameters($value)
	{
		return new KObjectConfigJson($value);
	}

	public function getBasePath()
	{
		return $this->getConfig()->base_path;
	}

	public function getAuthor()
	{
		$user = $this->getObject('user.provider')->load($this->author);
		return $user;
	}

	public function getContent()
	{
		static $prepared;

		//Prepare the content
		if(!$prepared)
		{
			JPluginHelper::importPlugin('content');

			$content = new stdClass;
			$content->text = $this->content;
			$params = (object) $this->parameters->toArray();
			$name   = $this->getIdentifier()->getName();

			JEventDispatcher::getInstance()->trigger(
				'onContentPrepare',
				['com_pages.'.$name, &$content, &$params, 0]
			);

			$this->content = $content->text;

			$prepared = true;
		}

		return $this->content;
	}

	public function getContentType()
	{
		return 'text/html';
	}

	public function getHash()
	{
		return hash("crc32b", $this->getContent());
	}

	public function __toString()
	{
		return $this->getContent();
	}
}
