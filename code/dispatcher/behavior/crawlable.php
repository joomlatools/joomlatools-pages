<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorCrawlable extends KControllerBehaviorAbstract
{
    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        if($context->page)
        {
            //Add a (self-referential) canonical URL (only to GET and HEAD requests)
            if($url = $context->subject->getController()->getView()->getCanonical()) {
                $context->response->getHeaders()->set('Link', ['<'.rtrim($url, '/').'>' => ['rel' => 'canonical']], false);
            }

            //Add X-Robots-Tag
            $metadata = $context->subject->getController()->getView()->getMetadata();
            if($metadata->has('robots'))
            {
                $tags = KObjectConfig::unbox($metadata->robots);
                $context->response->getHeaders()->set('X-Robots-Tag', $tags);
            }
        }
    }

    public function isSupported()
    {
        $mixer   = $this->getMixer();
        $request = $mixer->getRequest();

        //Support robots and canonical only for HTML GET and HEAD requests
        if(($request->isSafe()) && $request->getFormat() == 'html') {
            return true;
        }

        return false;
    }
}