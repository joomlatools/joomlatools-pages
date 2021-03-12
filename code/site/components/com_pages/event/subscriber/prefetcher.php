<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberPrefetcher extends ComPagesEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'options'  => [
                'selector' => 'a.prefetch',
                'onload'   => true,
                'onhover'  => true,
                'debug'    => JDEBUG
            ],
        ));

        parent::_initialize($config);
    }

    public function onBeforeDispatcherSend(KEventInterface $event)
    {
        $content = $event->getTarget()->getResponse()->getContent();

        if($event->getTarget()->getRequest()->getFormat() == 'html' && $event->getTarget()->isCacheable(true))
        {
            $page     = $event->getTarget()->getPage();
            $prefetch = $page->get('process/prefetch',  $this->getObject('com://site/pages.config')->get('http_static_cache', false));

            if($prefetch !== false)
            {
                $template = $event->getTarget()->getController()->getView()->getTemplate();

                if(is_scalar($prefetch))
                {
                    $config = $this->getConfig()->options;
                    $config->selector = is_string($prefetch) ? $prefetch : 'a.prefetch';
                }
                else $config = $prefetch->append($this->getConfig()->options);

                $prefetcher = $template->helper('behavior.prefetcher', $config);
                $template->getFilter('asset')->filter($prefetcher);

                $event->getTarget()->getResponse()->setContent($content.$prefetcher);
            }
        }
    }
}