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

        if(!$response->isDownloadable() && !$response->isRedirect())
        {
            $controller = $this->getObject('com:koowa.controller.page',  array('response' => $response));

            $controller->getView()
                ->setDecorator($this->getDecorator())
                ->setLayout($this->getLayout());

            $content = $controller->render();

            //Set the result in the response
            $response->setContent($content);
        }
    }

    public function getLayout()
    {
        $result = 'joomla';

        if(JDEBUG && $this->getResponse()->isError()) {
            $result = 'koowa';
        }

        return $result;
    }

    public function getDecorator()
    {
        $result = false;

        if($content = $this->getResponse()->getContent())
        {
            //Do not decorate if we are outputting a html document
            if(!preg_match('#<html(.*)>#siU', $content)) {
                $result = 'joomla';
            } else {
                $result = 'koowa';
            }
        }

        if(JDEBUG && $this->getResponse()->isError()) {
            $result = 'koowa';
        }

        return $result;
    }
}