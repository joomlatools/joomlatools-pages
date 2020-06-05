<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorRedirectable extends KControllerBehaviorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_HIGH,
        ));

        parent::_initialize($config);
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        $response = $context->response;
        $request  = $context->request;

        //If we are submitting a form and there is no redirect defined use the url of the page.
        if($request->isFormSubmit() && $request->getReferrer() && $response->isSuccess()) {
            $response->setRedirect($request->getUrl());
        }
    }
}