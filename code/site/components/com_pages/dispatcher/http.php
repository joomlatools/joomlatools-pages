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
    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        $request = $context->request;

        //Manually route the url if it hasn't been routed yet
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
        $method = strtolower($context->request->getMethod());

        if (!in_array($method, $this->getHttpMethods())) {
            throw new KDispatcherExceptionMethodNotAllowed('Method not allowed');
        }

        $this->execute($method, $context);
        $this->send($context);
    }
}