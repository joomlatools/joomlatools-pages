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
    public function setModel($model)
    {
        //Create the collection model
        $model = $this->getObject('com://site/pages.model.factory')
            ->createCollection($this->getPage()->path, $this->getRequest()->query->toArray());

        return parent::setModel($model);
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

        $identity_key = $entity->getIdentityKey();
        $identity     = $entity->getProperty($identity_key);

        //Set entity new identity in the state (to make it unique)
        if(!$this->getModel()->getState()->isUnique()) {
            $this->getModel()->getState()->set($identity_key, $identity);
        }

        //Generate the location for the resource
        $route    = $context->router->generate($this->getModel()->getPage(), [$identity_key => $identity]);
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

        if(count($entities))
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
        if(count($entities) && $this->getModel()->getState()->isUnique(false))
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