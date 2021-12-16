<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorDecoratable extends ComKoowaDispatcherBehaviorDecoratable
{
    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        $response = $context->getResponse();
        $request  = $context->getRequest();

        if(!$response->isDownloadable() && !$response->isRedirect() && $request->getFormat() == 'html')
        {
            $controller = $this->getObject('com:koowa.controller.page',  array('response' => $response));

            //Configure the page view
            $controller->getView()
                ->setDecorator($this->getDecorator())
                ->setDecorator($this->getDecorator())
                ->setLayout($this->getLayout());

            //Set the result in the response
            $response->setContent($controller->render());
        }
    }

    public function getLayout()
    {
        $result = 'joomla';

        if($this->getObject('pages.config')->debug && $this->getResponse()->isError()) {
            $result = 'koowa';
        }

        return $result;
    }

    public function getDecorator()
    {
        $result = null;

        if($this->getRequest()->getFormat() == 'html')
        {
            $result = 'joomla';

            if($content = $this->getResponse()->getContent())
            {
                //Do not decorate if we are outputting a html document
                if(!preg_match('#<html(.*)>#siU', $content)) {
                    $result = 'joomla';
                } else {
                    $result = 'koowa';
                }
            }
        }

        if($this->getObject('pages.config')->debug && $this->getResponse()->isError()) {
            $result = 'koowa';
        }

        return $result;
    }

    public function isDecorated()
    {
        return (bool) ($this->getDecorator() == 'joomla');
    }

    public function isSupported()
    {
        $mixer   = $this->getMixer();
        $request = $mixer->getRequest();

        // Support HTML GET requests and also form submits (so we can render errors on POST)
        if(($request->isFormSubmit() || $request->isGet())) {
            return true;
        }

        return false;
    }
}