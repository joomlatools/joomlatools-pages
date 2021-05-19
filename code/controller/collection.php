<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerCollection extends ComPagesControllerPage
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'behaviors' => ['validatable'],
        ));

        parent::_initialize($config);
    }

    public function setModel($model)
    {
        //Create the collection model
        $model = $this->getObject('model.factory')
            ->createModel($this->getPage()->path, $this->getRequest()->query->toArray(), false);

        return parent::setModel($model);
    }

    protected function _actionRender(KControllerContextInterface $context)
    {
        $result = false;

        $path = $context->request->getUrl()->getPath(true);

        //Check if we are rendering an empty form
        if(end($path) == 'new') {
            $action = 'read';
        }  else {
            $action = $this->getView()->isCollection() ? 'browse' : 'read';
        }

        //Execute the action
        if($result = $this->execute($action, $context) !== false)
        {
            if(!is_string($result) && !(is_object($result) && method_exists($result, '__toString'))) {
                $result = parent::_actionRender($context);
            }
        }

        return $result;
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
        $result = $this->getModel()->persist();

        //Only throw an error if the action explicitly failed.
        if($result === ComPagesModelInterface::PERSIST_FAILURE)
        {
            $error = $entity->getStatusMessage();
            throw new KControllerExceptionActionFailed($error ? $error : 'Add Action Failed');
        }

        //Set entity new identity in the state (to make it unique)
        if(!$this->getModel()->getState()->isUnique())
        {
            foreach($this->getModel()->getPrimaryKey() as $key){
                $this->getModel()->getState()->set($key, $entity->getProperty($key));
            }
        }

        //Generate the location for the resource
        $query = array();
        foreach($this->getModel()->getPrimaryKey() as $key){
            $query[$key] = $entity->getProperty($key);
        }

        $route    = $context->router->generate($this->getPage(), $query);
        $location = $context->router->qualify($route);

        //See: https://tools.ietf.org/html/rfc7231#page-52
        $context->response->setStatus(KHttpResponse::CREATED);
        $context->response->headers->set('Location', $location);

        /*
         * The Content-Location contains the new representation of that resource, thereby distinguishing it
         * from representations that might only report about the action (e.g., "It worked!").  This allows
         * authoring applications to update their local copies without the need for a subsequent GET request.
         *
         * See: https://tools.ietf.org/html/rfc7231#section-3.1.4.2
         */
        $context->response->headers->set('Content-Location', $location);

        return $entity;
    }

    protected function _actionEdit(KControllerContextInterface $context)
    {
        if(!$context->result instanceof KModelEntityInterface) {
            $entities = $this->getModel()->fetch();
        } else {
            $entities = $context->result;
        }

        if(count($entities) && $this->getModel()->isAtomic())
        {
            foreach($entities as $entity) {
                $entity->setProperties($context->request->data->toArray());
            }

            //Set the entity state
            $entities->save();

            //Persist the entity
            $result = $this->getModel()->persist();

            if($result === ComPagesModelInterface::PERSIST_NOCHANGE) {
                $context->response->setStatus(KHttpResponse::NO_CONTENT);
            }

            //Only throw an error if the action explicitly failed.
            if($result === ComPagesModelInterface::PERSIST_FAILURE)
            {
                $error = $entity->getStatusMessage();
                throw new KControllerExceptionActionFailed($error ? $error : 'Edit Action Failed');
            }
        }
        else throw new KControllerExceptionResourceNotFound('Resource Not Found');

        return $entities;
    }

    protected function _actionDelete(KControllerContextInterface $context)
    {
        if(!$context->result instanceof KModelEntityInterface) {
            $entities = $this->getModel()->fetch();
        } else {
            $entities = $context->result;
        }

        //Do not allow deleting a whole collection
        if(count($entities) && $this->getModel()->isAtomic())
        {
            foreach($entities as $entity) {
                $entity->setProperties($context->request->data->toArray());
            }

            //Set the entity state
            $entities->delete();

            //Persist the entity
            $result = $this->getModel()->persist();

            if($result === ComPagesModelInterface::PERSIST_SUCCESS) {
                $context->response->setStatus(KHttpResponse::NO_CONTENT);
            }

            //Only throw an error if the action explicitly failed.
            if($result === ComPagesModelInterface::PERSIST_FAILURE)
            {
                $error = $entities->getStatusMessage();
                throw new KControllerExceptionActionFailed($error ? $error : 'Delete Action Failed');
            }
        }
        else throw new KControllerExceptionResourceNotFound('Resource Not Found');

        return $entities;
    }
}