<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberFiledownloader extends ComPagesEventSubscriberAbstract
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
            //Set the location header
            $dispatcher = $this->getObject('com://site/pages.dispatcher.http');
            $response = $dispatcher->getResponse();

            //Force download the file
            if(isset($route->query['force-download']))
            {
                $request->query->set('force-download', true);
                unset($route->query['force-download']);
            }

            //Attach a different transport [stream or sendfile]
            if(isset($route->query['transport']))
            {
                $transport = $route->query['transport'];

                //Enable using the header
                if($transport == 'sendfile') {
                    $response->getHeaders()->set('X-Sendfile', 1);
                }

                $response->attachTransport($transport);

                unset($route->query['transport']);
            }

            //Set the cache time
            if(isset($route->query['cache']) && ctype_alnum($route->query['cache']))
            {
                $response->setMaxAge($route->query['cache']);
                unset($route->query['cache']);
            }

            //Qualify the route
            $route = $router->qualify($route);

            //Get the file path
            $path = $route->getPath();

            try {
                $response->setContent($path, @mime_content_type($path) ??  'application/octet-stream');
            } catch (InvalidArgumentException $e) {
                throw new KControllerExceptionResourceNotFound('File not found');
            }

            $dispatcher->send();
        }
    }
}
