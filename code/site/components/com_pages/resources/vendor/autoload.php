<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

spl_autoload_register(function ($class)
{
	if (strpos($class, 'Michelf\MarkdownExtra') === 0)
	{
		require_once __DIR__.'/markdown/MarkdownExtra.inc.php';
		return true;
	}

	if (strpos($class, 'Highlight\\') === 0)
	{
		require_once __DIR__.'/highlight/Autoloader.php';
		return \Highlight\Autoloader::load($class);
	}

	return false;
});