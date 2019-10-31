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
        $this->addCommandCallback('before.add', '_validateData');
        $this->addCommandCallback('before.edit', '_validateData');
        $this->addCommandCallback('before.submit', '_validateData');

        $this->addCommandCallback('after.add', '_processData');
        $this->addCommandCallback('after.edit', '_processData');
        $this->addCommandCallback('after.submit', '_processData');
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

    protected function _actionEdit(KControllerContextInterface $context)
    {
        if(!$context->result instanceof KModelEntityInterface) {
            $entities = $this->getModel()->fetch();
        } else {
            $entities = $context->result;
        }

        if(count($entities))
        {
            foreach($entities as $entity) {
                $entity->setProperties($context->request->data->toArray());
            }

            //Set the entity state
            $entities->save();

            //Persist the entity
            $result = $this->getModel()->store();

            //Only throw an error if the action explicitly failed.
            if($result === false)
            {
                $error = $entity->getStatusMessage();
                throw new KControllerExceptionActionFailed($error ? $error : 'Edit Action Failed');
            }

            //Only set the reset content status if the action explicitly succeeded
            if($result === true) {
                $context->response->setStatus(KHttpResponse::RESET_CONTENT);
            }
        }
        else throw new KControllerExceptionResourceNotFound('Resource Not Found');

        return $entities;
    }

    protected function _actionAdd(KControllerContextInterface $context)
    {
        if(!$context->result instanceof KModelEntityInterface) {
            $entity = $this->getModel()->create($context->request->data->toArray());
        } else {
            $entity = $context->result;
        }

        //Set the entity state
        $entity->save();

        //Persist the entity
        $result = $this->getModel()->store();

        //Only throw an error if the action explicitly failed.
        if($result === false)
        {
            $error = $entity->getStatusMessage();
            throw new KControllerExceptionActionFailed($error ? $error : 'Add Action Failed');
        }

        $key   = $entity->getIdentityKey();
        $route = $context->router->generate($this->getModel()->getPage(), [$key => $entity->$key]);

        $context->response->setStatus(KHttpResponse::CREATED);
        $context->response->headers->set('Location', $context->router->qualify($route));

        return $entity;
    }

    protected function _actionDelete(KControllerContextInterface $context)
    {
        if(!$context->result instanceof KModelEntityInterface) {
            $entities = $this->getModel()->fetch();
        } else {
            $entities = $context->result;
        }

        if(count($entities))
        {
            foreach($entities as $entity) {
                $entity->setProperties($context->request->data->toArray());
            }

            //Set the entity state
            $entities->delete();

            //Persist the entity
            $result = $this->getModel()->store();

            //Only throw an error if the action explicitly failed.
            if($result === false)
            {
                $error = $entities->getStatusMessage();
                throw new KControllerExceptionActionFailed($error ? $error : 'Delete Action Failed');
            }

            $context->response->setStatus(KHttpResponse::NO_CONTENT);
        }
        else throw new KControllerExceptionResourceNotFound('Resource Not Found');

        return $entities;
    }

    protected function _actionSubmit(KControllerContextInterface $context)
    {
        if($redirect = $this->getPage()->form->redirect)
        {
            $url = $this->getView()->getRoute($redirect);
            $this->getResponse()->setRedirect($url);
        }
    }

    public function setModel($model)
    {
        if($this->getPage()->form->model)
        {
            //Create the collection model
            $model = $this->getObject('com://site/pages.model.factory')
                ->createCollection($this->getPage()->form->model);

            $fields =  (array) KObjectConfig::unbox($this->getPage()->form->fields);

            foreach($fields as $field => $filters)
            {
                if(in_array('unique', $filters))
                {
                    $filters = array_diff($filters, ['unique']);
                    $model->getState()->insert($field, $filters, null, true);
                }
            }

            $model->setState($this->getRequest()->query->toArray());
        }

        return parent::setModel($model);
    }
}