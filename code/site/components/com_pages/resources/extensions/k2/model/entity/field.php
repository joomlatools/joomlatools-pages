<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtK2ModelEntityField extends ComPagesModelEntityItem
{
	protected function _initialize(KObjectConfig $config)
	{
		$config->append([
			'data' => [
				'id'	=> '',
				'name'  => '',
				'title' => '',
			],
		]);

		parent::_initialize($config);
	}

	public function getPropertyName()
	{
		$value = null;

		if($this->_value)
		{
			if(is_string($this->_value)) {
				$value = array_pop(json_decode($this->_value, true));
			}

			if($value['alias']) {
				$value = $value['alias'];
			} else {
				$value =  ucwords(str_replace(array('_', '-'), ' ', $this->title));
			}
		}

		return $value;
	}
}
