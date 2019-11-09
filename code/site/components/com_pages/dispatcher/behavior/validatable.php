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
    private $__rules;
    private $__honeypot;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'whitelist' => ['_method', '_action', '_format']
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

    public function setValidationRules(array $rules)
    {
        $this->__rules = $rules;
        return $this;
    }

    public function getValidationRules()
    {
        return (array) $this->__rules;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        if(!$context->request->isSafe())
        {
            if($page = $context->page)
            {
                if($page->isForm())
                {
                    $this->setHoneypot($page->form->honeypot);
                    $this->setValidationRules((array) KObjectConfig::unbox($page->form->fields));
                }

                if($page->isCollection())
                {
                    $this->setHoneypot($page->collection->honeypot);
                    $this->setValidationRules((array) KObjectConfig::unbox($page->collection->fields));
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
            $rules = $this->getValidationRules();
            $data  = $context->request->data;

            foreach($rules as $key => $filters)
            {
                $filters = (array) $filters;

                //Check if field is required
                if (in_array('required', $filters))
                {
                    if (!$data->has($key) || empty($data->get($key, 'raw'))) {
                        throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is required', ucfirst($key)));
                    }
                }
            }
        }
    }

    protected function _beforePut(KDispatcherContextInterface $context)
    {
        $this->validateRequest($context->request);

        //Check constraints
        $fields = array_keys($this->getValidationRules());

        foreach($fields as $field)
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
        $rules = $this->getValidationRules();

        //Add request query parameters that are defined in the schema (overriding existing values)
        foreach(array_diff_key($request->query->toArray(), $rules) as $key => $value) {
            $request->data->set($key, $value, true);
        }

        //Remove request data that is not defined in schema
        foreach(array_diff_key($request->data->toArray(), $rules) as $key => $value)
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
        $rules = $this->getValidationRules();
        foreach($rules as $key => $filters)
        {
            $filters = array_diff((array) $filters, ['required', 'unique']);

            //Check if field is valid
            if($data->has($key))
            {
                $value = $data->get($key, 'raw');

                $chain = $this->getObject('filter.factory')->createChain($filters);
                if(!$chain->validate($value)) {
                    throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is not valid', ucfirst($key)));
                }

                //Santize data just in case
                $data->set($key, $chain->sanitize($value));
            }
        }
    }
}