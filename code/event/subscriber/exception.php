<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberException extends ComPagesEventSubscriberAbstract
{
    public function isEnabled()
    {
        $result = parent::isEnabled();

        //Disable error handler if directly routing to a component
        if(isset($_REQUEST['option']) && substr($_REQUEST['option'], 0, 4) == 'com_') {
            $result = false;
        }

        return $result;
    }

    public function onAfterKoowaBootstrap(KEventInterface $event)
    {
        //Catch all Joomla exceptions
        if(class_exists('JError')) {
            JError::setErrorHandling(E_ERROR, 'callback', array($this, 'handleException'));
        }
    }

    public function onException(KEvent $event)
    {
        $dispatcher = $this->getObject('com:pages.dispatcher.http');
        $exception = $event->exception;

        //Purge cache
        if($exception->getCode() == KHttpResponse::NOT_FOUND)
        {
            if($dispatcher->isCacheable()) {
                $dispatcher->purge();
            }
        }

        if(!$this->getObject('pages.config')->debug && $this->getObject('request')->getFormat() == 'html')
        {
            //If the error code does not correspond to a status message, use 500
            $code = $exception->getCode();
            if(!isset(KHttpResponse::$status_messages[$code])) {
                $code = '500';
            }

            foreach([(int) $code, '500'] as $code)
            {
                if($page = $dispatcher->getPage($code))
                {
                    $dispatcher->getPage()->setProperties($page);

                    //Set status code (before rendering the error)
                    $dispatcher->getResponse()->setStatus($code);

                    //Render the error
                    $content = $dispatcher->getController()->render($exception);

                    //Set error in the response
                    $dispatcher->getResponse()->setContent($content);
                    $dispatcher->send();
                }
            }
        }
    }

    public function handleException(\Exception $exception)
    {
        $this->getObject('exception.handler')->handleException($exception);
    }
}