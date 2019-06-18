<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberHtmlpurifier extends KEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_LOW,
            'indent'         => true,
            'vertical-space' => true,
            'wrap'           => 0,
            'drop-empty-elements' => false,
            'coerce-endtags'      => false,
            'beautify'            => JDEBUG ? true : false,
        ));

        parent::_initialize($config);
    }

    public function onBeforeDispatcherSend(KEventInterface $event)
    {
        if(class_exists('Tidy') && $this->getConfig()->beautify)
        {
            $content = $event->getTarget()->getResponse()->getContent();

            if($result = $this->_beautifyHtml($content)) {
                $event->getTarget()->getResponse()->setContent($result);
            }
        }
    }

    public function onAfterApplicationRender(KEventInterface $event)
    {
        if(class_exists('Tidy') && $this->getConfig()->beautify)
        {
            $body   = $event->getTarget()->getBody();

            if($result = $this->_beautifyHtml($body)) {
                $event->getTarget()->setBody($result);
            }
        }
    }

    protected function _beautifyHtml($content)
    {
        $result = false;

        if(strpos($content, '<head>'))
        {
            $tidy = new Tidy();
            $tidy->parseString($content,  $this->getConfig()->toArray(), 'utf8');

            $result = (string) $tidy;
        }

        return $result;
    }
}

