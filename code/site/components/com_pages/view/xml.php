<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewXml extends KViewTemplate
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors'  => ['routable', 'pageable'],
            'auto_fetch' => false,
        ]);

        parent::_initialize($config);
    }

    protected function _actionRender(KViewContext $context)
    {
        //Prepend the xml prolog
        $content  = '<?xml version="1.0" encoding="utf-8" ?>';
        $content .= $this->getContent();

        return trim($content);
    }

    public function getTitle()
    {
        $result = '';
        if($page = $this->getModel()->getPage()) {
            $result = $page->title ? $page->title :  '';
        }

        return $result;
    }

    public function getUrl($url = null)
    {
        $url = clone parent::getUr(l);

        if($url) {
            $url->setUrl($url);
        }

        return $this;
    }

    public function getRoute($page = null, $query = array(), $escape = false)
    {
        return $this->getBehavior('routable')->getRoute($page, $query, $escape);
    }
}