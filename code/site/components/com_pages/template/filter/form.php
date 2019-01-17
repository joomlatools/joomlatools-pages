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
    /**
     * Handle form replacements
     *
     * @param string
     * @return $this
     */
    public function filter(&$text)
    {
        //$this->_addMetatag($text);
        $this->_addAction($text);
        //$this->_addToken($text);
        $this->_addQueryParameters($text);

        return $this;
    }
}