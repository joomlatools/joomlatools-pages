<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherHttp extends ComKoowaDispatcherHttp
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors' => ['cacheable'],
        ]);

        parent::_initialize($config);
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        $url = $context->request->getUrl();

        //Throw 4054 if the page cannot be found
        if($query = $this->getObject('com:pages.dispatcher.router.route')->parse($url)) {
            $context->request->query->add($query);
        } else {
            throw new KHttpExceptionNotFound('Page Not Found');
        }
    }

    protected function _actionDispatch(KDispatcherContextInterface $context)
    {
        //Throw 405 if the method is not allowed
        $method = strtolower($context->request->getMethod());
        if (!in_array($method, $this->getHttpMethods())) {
            throw new KDispatcherExceptionMethodNotAllowed('Method not allowed');
        }

        //Execute the component method
        $this->execute($method, $context);

        KDispatcherAbstract::_actionDispatch($context);
    }
}