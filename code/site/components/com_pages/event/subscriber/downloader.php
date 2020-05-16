<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberDownloader extends ComPagesEventSubscriberAbstract
{
	protected function _initialize(KObjectConfig $config)
	{
		$config->append(array(
			'priority' => KEvent::PRIORITY_HIGH,
		));

		parent::_initialize($config);
	}

	public function onAfterApplicationRoute(KEventInterface $event)
	{
		$request = $this->getObject('request');
		$router  = $this->getObject('com://site/pages.dispatcher.router.file', ['request' => $request]);

		if(false !== $route = $router->resolve())
		{
			//Qualify the route
			$path = (string) $router->qualify($route, true);

			//Set the location header
			$dispatcher = $this->getObject('com://site/pages.dispatcher.http');

			try
			{
				$dispatcher->getResponse()
					->setContent((string) $path, @mime_content_type($path) ?? 'application/octet-stream');
			}
			catch (InvalidArgumentException $e) {
				throw new KControllerExceptionResourceNotFound('File not found');
			}

			$dispatcher->send();
		}
	}
}
