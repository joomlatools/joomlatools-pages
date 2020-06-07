<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtK2ModelEntityAttachment extends ComPagesModelEntityItem
{
	protected function _initialize(KObjectConfig $config)
	{
		$config->append([
			'data' => [
				'id'	 => '',
				'title'  => '',
				'url' 	 => '',
				'alt'	 => '',
				'impressions' => 0,
			],
			'internal_properties' => [ 'id'],
			'base_path'           => $this->getObject('request')->getBasePath(),
		]);

		parent::_initialize($config);
	}

	public function setPropertyUrl($value)
	{
		if($value)
		{
			$path = '/media/k2/attachments/'.$value;

			if(file_exists(JPATH_ROOT.$path))
			{
				$url   = $this->getBasePath().$path;
				$value = $this->getObject('lib:http.url')->setUrl($url);
			}
		}

		return $value;
	}

	public function getBasePath()
	{
		return $this->getConfig()->base_path;
	}
}
