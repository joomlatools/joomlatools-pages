<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

JLoader::register('ContentHelperRoute', JPATH_SITE.'/components/com_content/helpers/route.php');

class ExtJoomlaModelEntityCategory extends ExtJoomlaModelEntityAbstract
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
 				'date'        => 'now',
				'edited_date' => '',
				'author'      => '',
				'editor'      => '',
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
				'impressions' => 0,
				'direction'   => 'auto',
				'language'    => 'en-GB',
			],
		]);

		parent::_initialize($config);
	}

	public function getPropertyImage()
	{
		$image = array();

		if($this->parameters->image)
		{
			$url = $this->parameters->image;

			if(is_string($url) && strpos($url, '://') === false) {
				$url =  $this->getBasePath().'/'.ltrim($url, '/');
			}

			$url = $this->getObject('lib:http.url')->setUrl($url);

			$image = [
				'url'      => $url,
				'alt'      => $this->parameters->image_alt ?? '',
				'caption'  => '',
			];
		}

		return new ComPagesObjectConfig($image);
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

	public function setPropertyParameters($value)
	{
		if($value && is_string($value)) {
			$value = json_decode($value, true);
		}

		//$params = JComponentHelper::getParams('com_content')->toArray();

		$config  = new ComPagesObjectConfig(/*$params*/);
		$config->merge($value); // Override global params with article specific params

		return $config;
	}

	public function getEditor()
	{
		$user = $this->getObject('user.provider')->load($this->editor);
		return $user;
	}

	public function getRoute()
	{
		return JRoute::_(ContentHelperRoute::getCategoryRoute($this->id.':'.$this->slug));
	}
}
