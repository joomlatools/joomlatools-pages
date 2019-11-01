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

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Setup callbacks
        $this->addCommandCallback('before.submit', '_validateData');
        $this->addCommandCallback('after.submit' , '_processData');
    }

    protected function _validateData(KControllerContextInterface $context)
    {
        $page = $this->getPage();

        //Validate the request
        $this->setHoneypot($page->form->honeypot);
        $this->setValidationRules((array) KObjectConfig::unbox($this->getPage()->form->fields));
        $this->validateRequest();
    }

    protected function _processData(KControllerContextInterface $context)
    {
        if($this->isValidRequest())
        {
            $page = $this->getPage();

            $channel    = $page->form->name ?? $page->slug;
            $processors = (array) KObjectConfig::unbox($page->form->processors);

            $this->processData($context->request->data->toArray(), $processors, $channel);
        }
    }

    protected function _actionSubmit(KControllerContextInterface $context)
    {
        if($redirect = $this->getPage()->form->redirect)
        {
            $url = $this->getView()->getRoute($redirect);
            $this->getResponse()->setRedirect($url);
        }
    }
}