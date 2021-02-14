<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaCmsView extends ExtJoomlaCmsViewAbstract
{
	public function display($tpl = null)
	{
		//Locate the override
		if($override = $this->getOverride())
		{
			$result = $this->getObject('ext:joomla.view.override.html')
				->setDelegate($this)
				->setLayout($override)
				->render($this->getProperties());

			echo $result;
		}
		else parent::display($tpl);

		return $result;
	}

	public function getOverride($tpl = null)
	{
		$result = false;

		$name     = $this->getName();
		$layout   = $this->getLayout();
		$component = str_replace('com_', '', basename(JPATH_COMPONENT));

		// Create the template file name based on the layout
		$file     = isset($tpl) ? $layout . '_' . $tpl : $layout;
		$override = 'page://overrides/'.$component.'/'.$name.'/'.$file;

		if($this->getObject('template.locator.factory')->locate($override)) {
			$result = $override;
		}

		return $result;
	}

	public function getObject($identifier)
	{
		return KObjectManager::getInstance()->getObject($identifier);
	}
}
