<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

trait ComPagesViewTraitModellable
{
    public function getPage($path = null)
    {
        if(!is_null($path)) {
            $result = $this->getObject('com://site/pages.model.factory')->createPage($path);
        } else {
            $result = $this->getModel()->getPage();
        }

        return $result;
    }

    public function getCollection($model = '', $state = array())
    {
        if($model) {
            $result = $this->getObject('com://site/pages.model.factory')->createCollection($model, $state)->fetch();
        } else {
            $result = $this->getModel()->fetch();
        }

        return $result;
    }

    public function getState()
    {
        return $this->getModel()->getState();
    }

    public function getTitle()
    {
        $title = '';
        if($page = $this->getPage())
        {
            $title = $page->title ?: '';

            if($this->getState()->isUnique()) {
                $title = $this->getCollection()->get('title', $title);
            }
        }

        return $title;
    }

    public function getDirection()
    {
        $result = 'auto';

        if($page = $this->getPage()) {
            $result = $page->direction ?: 'auto';
        }

        if($this->getModel()->getState()->isUnique())
        {
            if($direction = $this->getCollection()->direction) {
                $result = $direction;
            }
        }

        return $result;
    }

    public function getLanguage()
    {
        $result = 'en-GB';
        if($page = $this->getPage()) {
            $result = $page->language ?: 'en-GB';
        }

        if($this->getModel()->getState()->isUnique())
        {
            if($language = $this->getCollection()->language) {
                $result = $language;
            }
        }

        return $result;
    }

    public function isCollection()
    {
        return (bool) !$this->getState()->isUnique();
    }
}
