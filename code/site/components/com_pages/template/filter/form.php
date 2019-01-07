<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
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