<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerBehaviorValidatable extends KControllerBehaviorAbstract
{
    protected function _beforeSubmit(KControllerContextInterface $context)
    {
        if($context->page && $context->page->isSubmittable())
        {
            $schema = (array)KObjectConfig::unbox($context->page->form->schema);

            //Validate the request data based on the schema definition
            $this->_validateData($context->request, $schema);

            //Check required attributes are present
            $this->_validateRequired($context->request, $schema);
        }
    }

    protected function _beforeAdd(KControllerContextInterface $context)
    {
        if($context->page && $context->page->isEditable())
        {
            $schema = (array)KObjectConfig::unbox($context->page->collection->schema);

            //Santize the request
            $this->_sanitizeRequest($context->request, $schema);

            //Validate the request data based on the schema definition
            $this->_validateData($context->request, $schema);

            //Check required attributes are present
            $this->_validateRequired($context->request, $schema);

            //Do not allow identity key to be in the request data
            $this->_validateIdentityKey($context->request);
        }
    }

    protected function _beforeEdit(KControllerContextInterface $context)
    {
        if($context->page && $context->page->isEditable())
        {
            $schema = (array) KObjectConfig::unbox($context->page->collection->schema);

            //Santize the request
            $this->_sanitizeRequest($context->request, $schema);

            //Validate the request data based on the schema definition
            $this->_validateData($context->request, $schema);

            //Do not allow identity key to be in the request data
            $this->_validateIdentityKey($context->request);
        }
    }

    protected function _beforeDelete(KControllerContextInterface $context)
    {
        if($context->page && $context->page->isEditable())
        {
            $schema = (array) KObjectConfig::unbox($context->page->collection->schema);

            //Santize the request
            $this->_sanitizeRequest($context->request, $schema);

            //Validate the request data based on the schema definition
            $this->_validateData($context->request, $schema);

            //Do not allow identity key to be in the request data
            $this->_validateIdentityKey($context->request);
        }
    }

    protected function _sanitizeRequest(KControllerRequestInterface $request, array $schema)
    {
        //Remove internal model states from query
        foreach($this->getModel()->getState() as $state)
        {
            if($state->internal) {
                $request->query->remove($state->name);
            }
        }

        //Add request query parameters that are defined in the schema (overriding existing values)
        foreach(array_diff_key($request->query->toArray(), $schema) as $key => $value) {
            $request->data->set($key, $value, true);
        }

        //Remove request data that is not defined in schema
        foreach(array_diff_key($request->data->toArray(), $schema) as $key => $value) {
            $request->data->remove($key);
        }
    }

    protected function _validateData(KControllerRequestInterface $request, array $schema)
    {
        //Check if attributes are valid
        foreach($schema as $field => $constraints)
        {
            $filters = array_diff((array) $constraints, ['required', 'unique']);

            if($request->data->has($field))
            {
                $value = $request->data->get($field, 'raw');

                $chain = $this->getObject('filter.factory')->createChain($filters);
                if(!$chain->validate($value)) {
                    throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is not valid', ucfirst($field)));
                }

                //Santize data just in case
                $request->data->set($field, $chain->sanitize($value));
            }
        }
    }

    protected function _validateRequired(KControllerRequestInterface $request, array $schema)
    {
        foreach ($schema as $field => $constraints)
        {
            $constraints = (array)$constraints;

            //Check if field is required
            if (in_array('required', $constraints))
            {
                if (!$request->data->has($field) || empty($request->data->get($field, 'raw'))) {
                    throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is required', ucfirst($field)));
                }
            }
        }
    }

    protected function _validateIdentityKey(KControllerRequestInterface $request)
    {
        //Check for identity key
        if($identity_key = $this->getModel()->getIdentityKey())
        {
            if($request->data->has($identity_key)) {
                throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is an identity key', ucfirst($identity_key)));
            }
        }
    }
}