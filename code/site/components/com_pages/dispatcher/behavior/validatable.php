<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorValidatable extends KControllerBehaviorAbstract
{
    private $__schema;
    private $__honeypot;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'whitelist' => ['_method', '_action', 'format']
        ));

        parent::_initialize($config);
    }

    public function setHoneypot($name)
    {
        $this->__honeypot = $name;
        return $this;
    }

    public function getHoneypot()
    {
        return $this->__honeypot;
    }

    public function setCollectionSchema(array $rules)
    {
        $this->__schema = $rules;
        return $this;
    }

    public function getCollectionSchema()
    {
        return (array) $this->__schema;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        if(!$context->request->isSafe())
        {
            $content_types = array('application/json', 'application/x-www-form-urlencoded');

            if(!in_array($context->request->getContentType(), $content_types)) {
                throw new KHttpExceptionUnsupportedMediaType();
            }

            if($page = $context->page)
            {
                if($page->isForm())
                {
                    $this->setHoneypot($page->form->honeypot);
                    $this->setCollectionSchema((array) KObjectConfig::unbox($page->form->schema));
                }

                if($page->isCollection())
                {
                    $this->setHoneypot($page->collection->honeypot);
                    $this->setCollectionSchema((array) KObjectConfig::unbox($page->collection->schema));
                }
            }

            $this->sanitizeRequest($context->request);
        }
    }

    protected function _beforePost(KDispatcherContextInterface $context)
    {
        $this->validateRequest($context->request);

        //Check constraints
        if(!$this->getController()->getModel()->getState()->isUnique(false))
        {
            $schema = $this->getCollectionSchema();
            $data   = $context->request->data;

            foreach($schema as $field => $constraints)
            {
                $constraints = (array) $constraints;

                //Check if field is required
                if (in_array('required', $constraints))
                {
                    if (!$data->has($field) || empty($data->get($field, 'raw'))) {
                        throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is required', ucfirst($field)));
                    }
                }
            }
        }
    }

    protected function _beforePut(KDispatcherContextInterface $context)
    {
        $this->validateRequest($context->request);

        //Check constraints
        $schema = array_keys($this->getCollectionSchema());

        foreach($schema as $field)
        {
            if(!$context->request->data->has($field)) {
                throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is required', ucfirst($field)));
            }
        }
    }

    protected function _beforePatch(KDispatcherContextInterface $context)
    {
        $this->validateRequest($context->request);
    }

    protected function _beforeDelete(KDispatcherContextInterface $context)
    {
        $this->validateRequest($context->request);
    }

    public function sanitizeRequest(KDispatcherRequestInterface $request)
    {
        $schema = $this->getCollectionSchema();

        //Add request query parameters that are defined in the schema (overriding existing values)
        foreach(array_diff_key($request->query->toArray(), $schema) as $key => $value) {
            $request->data->set($key, $value, true);
        }

        //Remove request data that is not defined in schema
        foreach(array_diff_key($request->data->toArray(), $schema) as $key => $value)
        {
            if(!$this->getConfig()->whitelist->has($key)) {
                $request->data->remove($key);
            }
        }
    }

    public function validateRequest(KDispatcherRequestInterface $request)
    {
        $data = $request->getData();

        //Process honeypot
        if($honeypot = $this->getHoneyPot())
        {
            if($data->get($honeypot, 'raw')) {
                throw new ComPagesControllerExceptionRequestBlocked('Spam attempt blocked');
            }
        }

        //Validate data
        $schema = $this->getCollectionSchema();
        foreach($schema as $field => $constraints)
        {
            $filters = array_diff((array) $constraints, ['required', 'unique']);

            //Check if field is valid
            if($data->has($field))
            {
                $value = $data->get($field, 'raw');

                $chain = $this->getObject('filter.factory')->createChain($filters);
                if(!$chain->validate($value)) {
                    throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is not valid', ucfirst($field)));
                }

                //Santize data just in case
                $data->set($field, $chain->sanitize($value));
            }
        }
    }
}