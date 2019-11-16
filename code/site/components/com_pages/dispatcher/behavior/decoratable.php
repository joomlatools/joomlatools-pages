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
            $decorator = $this->getDecorator();

            //Set metadata in Joomla document
            if($decorator == 'joomla')
            {
                //Set the title
                if($title = $this->getController()->getView()->getTitle()) {
                    JFactory::getDocument()->setTitle($title);
                }

                //Set the direction
                if($direction = $this->getController()->getView()->getDirection()) {
                    JFactory::getDocument()->setDirection($direction);
                }

                //Set the language
                if($language = $this->getController()->getView()->getLanguage()) {
                    JFactory::getDocument()->setLanguage($language);
                }
            }

            $controller = $this->getObject('com:koowa.controller.page',  array('response' => $response));

            $controller->getView()
                ->setDecorator($decorator)
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
        $result = 'joomla';

        if($this->getRequest()->getFormat() == 'html')
        {
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

        if(JDEBUG && $this->getResponse()->isError()) {
            $result = 'koowa';
        }

        return $result;
    }
}