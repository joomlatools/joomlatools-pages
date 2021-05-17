<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorRedirectable extends KControllerBehaviorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_HIGH,
        ));

        parent::_initialize($config);
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        $router = $this->getObject('com:pages.dispatcher.router.redirect', ['request' => $context->request]);

        if(false !== $route = $router->resolve())
        {
            if($route->toString(KHttpUrl::AUTHORITY))
            {
                //External redierct: 301 permanent
                $status = KHttpResponse::MOVED_PERMANENTLY;
            }
            else
            {
                //Internal redirect: 307 temporary
                $status = KHttpResponse::TEMPORARY_REDIRECT;
            }

            //Qualify the route
            $url = $router->qualify($route);

            //Set the location header
            $context->getResponse()->getHeaders()->set('Location',  $url);
            $context->getResponse()->setStatus($status);

            //Purge the cache
            $dispatcher = $this->getObject('com:pages.dispatcher.http');
            if($dispatcher->isCacheable()) {
                $dispatcher->purge();
            }

            $context->getSubject()->send();
        }
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        $response = $context->response;
        $request  = $context->request;

        //If we are submitting a form and there is no redirect defined use the url of the page.
        if($request->isFormSubmit() && $request->getReferrer() && $response->isSuccess()) {
            $response->setRedirect($request->getUrl());
        }
    }
}