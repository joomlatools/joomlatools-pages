<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerForm extends ComPagesControllerPage
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'behaviors' => ['validatable', 'processable'],
        ));

        parent::_initialize($config);
    }

    protected function _actionSubmit(KControllerContextInterface $context)
    {
        $page = $this->getModel()->getPage();

        //Validate the request
        $this->setHoneypot($page->form->honeypot);
        $this->setValidationtRules((array) KObjectConfig::unbox($page->form->rules));

        if($data = $this->validateRequest())
        {
            $channel    = $page->form->name ?? $page->slug;
            $processors = (array) KObjectConfig::unbox($page->form->processors);

            $this->processData($data, $processors, $channel);
        }
    }

    protected function _afterSubmit(KControllerContextInterface $context)
    {
        //Set the redirect if defined
        $form = $this->getModel()->getPage()->form;

        if($redirect = $form->redirect)
        {
            $url = $this->getView()->getRoute($redirect);
            $this->getResponse()->setRedirect($url);
        }
    }
}