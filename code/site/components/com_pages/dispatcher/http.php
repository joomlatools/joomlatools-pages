<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherHttp extends ComKoowaDispatcherHttp
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors' => ['cacheable'],
        ]);

        parent::_initialize($config);
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        $request = $context->request;

        //Manualy route the url if it hasn't been routed yet
        if(!isset($request->query->path))
        {
            $base = $request->getBasePath();
            $url  = $request->getUrl()->getPath();

            //Get the segments
            $path = trim(str_replace(array($base, '/index.php'), '', $url), '/');

            if($path) {
                $segments = explode('/', $path);
            } else {
                $segments = array('index');
            }

            //Route the
            $query = $this->getObject('com:pages.router')->parse($segments);

            $request->query->add($query);
        }
    }

    protected function _actionDispatch(KDispatcherContextInterface $context)
    {
        //Throw 404 if the route is not valid
        if(!$this->getObject('com:pages.router')->getPage()) {
            throw new KHttpExceptionNotFound('Page Not Found');
        }

        //Throw 405 if the method is not allowed
        $method = strtolower($context->request->getMethod());
        if (!in_array($method, $this->getHttpMethods())) {
            throw new KDispatcherExceptionMethodNotAllowed('Method not allowed');
        }

        //Execute the request
        $this->execute($method, $context);
        $this->send($context);
    }

    protected function _afterDispatch(KDispatcherContextInterface $context)
    {
        $path = $this->getObject('com:pages.router')->getPath();

        $pathway = JFactory::getApplication()->getPathway();
        $menu    = JFactory::getApplication()->getMenu()->getActive();

        $segments = array();
        foreach($path as $segment)
        {
            $segments[] = $segment;
            $pathway->addItem($segment, 'index.php?path='.implode('/', $path));
        }
    }
}