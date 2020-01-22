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
    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        if(!$context->request->isSafe())
        {
            //Validate the content type
            $content_types = array('application/json', 'application/x-www-form-urlencoded');
            if(!in_array($context->request->getContentType(), $content_types)) {
                throw new KHttpExceptionUnsupportedMediaType();
            }

            if($page = $context->page)
            {
                //Validate the honeypot
                if($page->isSubmittable())
                {
                    if($honeypot = $page->form->honeypot)
                    {
                        if($context->request->data->get($honeypot, 'raw')) {
                            throw new ComPagesControllerExceptionRequestBlocked('Spam attempt blocked');
                        } else {
                            $context->request->data->remove($honeypot);
                        }
                    }
                }
            }
        }
    }

    protected function _beforePut(KDispatcherContextInterface $context)
    {
        if($page = $context->page)
        {
            //Check constraints
            if($page->isEditable())
            {
                foreach($page->collection->schema as $name => $constraints)
                {
                    if(!$context->request->data->has($name)) {
                        throw new ComPagesControllerExceptionRequestInvalid(sprintf('%s is required', ucfirst($name)));
                    }
                }
            }
        }
    }
}