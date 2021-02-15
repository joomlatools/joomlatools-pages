<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

trait ComPagesViewTraitPage
{
    use ComPagesViewTraitCollection;

    public function getPage($path = null)
    {
        if(!is_null($path)) {
            $result = $this->getObject('page.registry')->getPageEntity($path);
        } else {
            $result = $this->getModel()->getPage();
        }

        return $result;
    }

    public function getTitle()
    {
        $title = '';
        if($page = $this->getPage()) {
            $title = $page->title ?: '';
        }

        if($this->getState()->isUnique()) {
            $title = $this->getCollection()->get('title', $title);
        }

        return $title;
    }

    public function getDirection()
    {
        $direction = 'auto';

        if($page = $this->getPage()) {
            $direction = $page->direction ?: 'auto';
        }

        if($this->getModel()->getState()->isUnique()) {
           $direction = $this->getCollection()->get('direction', $direction);
        }

        return $direction;
    }

    public function getLanguage()
    {
        $language = 'en-GB';
        if($page = $this->getPage()) {
            $language = $page->language ?: 'en-GB';
        }

        if($this->getModel()->getState()->isUnique()) {
            $language = $this->getCollection()->get('language', $language);
        }

        return $language;
    }
}
