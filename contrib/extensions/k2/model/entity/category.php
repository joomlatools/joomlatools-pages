<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtK2ModelEntityCategory extends ExtK2ModelEntityAbstract
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
				'image'      => [
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
				'impressions' => null,
				'direction'   => 'auto',
				'language'    => 'en-GB',
			],
			//metatags are used internally for calculating metadata through getter
			'internal_properties' => [],
		]);

		parent::_initialize($config);
	}

	public function getPropertySummary()
	{
		return $this->parameters->catMetaDesc;
	}

	public function getPropertyAuthor()
	{
		return $this->parameters->catMetaAuthor;
	}

	public function getPropertyMetadata()
	{
		$metadata = parent::getPropertyMetadata();

		if($robots = $this->parameters->get('catMetaRobots')) {
			$metadata->robots = $robots;
		}

		return $metadata;
	}

	public function setPropertyImage($value)
	{
		$image = array();

		if($value)
		{
			$path = '/media/k2/categories/'.$value;

			if(file_exists(JPATH_ROOT.$path))
			{
				$url = $this->getBasePath().$path;
				$url = $this->getObject('lib:http.url')->setUrl($url);

				$image = [
					'url'      => $url,
					'alt'      => '',
					'caption'  => '',
				];
			}
		}

		return new ComPagesObjectConfig($image);
	}

	public function setPropertyParameters($value)
	{
		if($value && is_string($value)) {
			$value = json_decode($value, true);
		}

		//$params = JComponentHelper::getParams('com_k2')->toArray();

		$config  = new ComPagesObjectConfig(/*$params*/);
		$config->merge($value); // Override global params with article specific params

		return $config;
	}
}
