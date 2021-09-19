<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterForm extends KTemplateFilterForm
{
    public function filter(&$text)
    {
        if($this->getTemplate()->page()->isForm()) {
            parent::filter($text);
        }

        return $this;
    }

    protected function _addAction(&$text)
    {
        //Action can be empty in HTML5 forms
        return $this;
    }
}