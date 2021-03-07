<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJ2storeModelEntityProduct extends ExtJ2storeModelEntityAbstract
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
                'product' => [
                    'type'     => '',
                    'button'   => '',
                    'visible'  => '',
                    'enabled'  => 0,
                    'price'    => '',
                    'tag'      => '',
                    'currency' => 'USD'

                ],
 				'category'    => [],
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
					'og:type'        => 'product',
					'og:title'       => null,
					'og:url'         => null,
					'og:image'       => null,
					'og:description' => null,

					//http://ogp.me/ns/article
					'product:plural_title'   => null,
					'product:price:amount'   => null,
					'product:price:currency' => null,
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

        if(!empty( $this->_product_price)) {
            $metadata->set('product:price:amount', (string)  $this->_product_price);
        }

        if(!empty($this->_product_currency)) {
            $metadata->set('product:price:currency', 'USD');
        }

        return $metadata;
    }

	public function getPropertyFields()
	{
		$fields = array();

		$rows = $this->getObject('ext:joomla.model.fields')
						->article($this->id)
						->fetch();

		foreach($rows as $row) {
			$fields[] = $row;
		}

		return $fields;
	}

	public function getPropertyProduct()
	{
		$product = [
			'type'     => $this->_product_type,
			'button'   => $this->_product_button,
			'visible'  => $this->_product_visible,
			'enabled'  => $this->_product_enabled,
			'price'    => $this->_product_price,
			'tag'      => $this->_product_tag,
			'currency' => 'USD',
		];

		if($this->_product_image_main)
		{
			$url = $this->_product_image_main;

			if(is_string($url) && strpos($url, '://') === false) {
				$url = $this->getBasePath().'/'.ltrim($url, '/');
			}

			$url = $this->getObject('lib:http.url')->setUrl($url);

			$product['image'] = [
				'url'      => $url,
				'alt'      => $this->_product_image_main_alt ?? '',
				'caption'  => '',
			];
		}
		if($this->_product_image_thumb)
		{
			$url = $this->_product_image_thumb;

			if(is_string($url) && strpos($url, '://') === false) {
				$url =  $this->getBasePath().'/'.ltrim($url, '/');
			}

			$url = $this->getObject('lib:http.url')->setUrl($url);

			$product['image'] = [
				'url'      => $url,
				'alt'      => $this->_product_image_thumb_alt ?? '',
				'caption'  => '',
			];
		}

		return new ComPagesObjectConfig($product);
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
		if(is_string($value)) {
			$value = json_decode($value, true);
		}

		//Normalize images
		$image = array();

		if($value['image_fulltext'])
		{
			$url = $value['image_fulltext'];

			if(is_string($url) && strpos($url, '://') === false) {
				$url = $this->getBasePath().'/'.ltrim($url, '/');
			}

			$url = $this->getObject('lib:http.url')->setUrl($url);

			$image = [
				'url'      => $url,
				'alt'      => $value['image_fulltext_alt'] ?? '',
				'caption'  => $value['image_fulltext_caption'] ?? '',
			];
		}
		elseif($value['image_intro'])
		{
			$url = $value['image_intro'];

			if(is_string($url) && strpos($url, '://') === false) {
				$url =  $this->getBasePath().'/'.ltrim($url, '/');
			}

			$url = $this->getObject('lib:http.url')->setUrl($url);

			$image = [
				'url'      => $url,
				'alt'      => $value['image_intro_alt'] ?? '',
				'caption'  => $value['image_intro_caption'] ?? '',
			];
 		}

		return new ComPagesObjectConfig($image);
	}

	public function setPropertyLinks($value)
	{
		if(is_string($value)) {
			$value = array_filter(json_decode($value, true));
		}

		$links = array();
		foreach(['a', 'b', 'c'] as $name)
		{
			if(isset($value['url'.$name]))
			{
				$links[] = [
					'url'  => $value['url'.$name],
					'text' => $value['url'.$name.'text']
				];
			}
		}

		return new ComPagesObjectConfig($links);
	}

	public function setPropertyParameters($value)
	{
		if(is_string($value)) {
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
}
