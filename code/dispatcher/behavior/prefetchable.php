<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorPrefetchable extends KControllerBehaviorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH,
            'options'  => [
                'selector' => 'a.prefetch',
                'onload'   => true,
                'onhover'  => true,
                'debug'    => $this->getObject('pages.config')->debug
            ],
        ));

        parent::_initialize($config);
    }

    protected function _beforeSend(KDispatcherContextInterface $context)
    {
        $content = $context->getResponse()->getContent();

        if($context->subject->getRequest()->getFormat() == 'html' && $context->subject->isCacheable(true))
        {
            $page     = $context->subject->getPage();
            $prefetch = $page->get('process/prefetch',  $this->getObject('pages.config')->get('http_static_cache', false));

            if($prefetch !== false)
            {
                $template = $context->subject->getController()->getView()->getTemplate();

                if(is_scalar($prefetch))
                {
                    $options = $this->getConfig()->options;
                    $options->selector = is_string($prefetch) ? $prefetch : 'a.prefetch';
                }
                else $options = $prefetch->append($this->getConfig()->options);

                $prefetcher = $template->helper('prefetcher', ['options' => KObjectConfig::unbox($options)]);
                $template->getFilter('asset')->filter($prefetcher);

                $context->subject->getResponse()->setContent($content.$prefetcher);
            }
        }
    }
}