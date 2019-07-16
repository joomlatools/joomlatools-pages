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
        if($form = $this->getTemplate()->page()->isForm())
        {
            parent::filter($text);

            if($name = $form->honeypot) {
                $this->_addHoneypot($text, $name);
            }
        }

        return $this;
    }

    protected function _addAction(&$text)
    {
        // All: Add the action if left empty
        if (preg_match_all('#<\s*form[^>]+action=""#si', $text, $matches, PREG_SET_ORDER))
        {
            $page   = $this->getTemplate()->page();
            $action = $this->getTemplate()->route($page);

            foreach ($matches as $match)
            {
                $str = str_replace('action=""', 'action="' . $action . '"', $match[0]);
                $text = str_replace($match[0], $str, $text);
            }
        }

        // POST: Add submit action
        $text    = preg_replace('#(<\s*form[^>]+method="post"[^>]*>)#si',
            '\1'.PHP_EOL.'<input type="hidden" name="_action" value="submit" />',
            $text
        );

        return $this;
    }

    protected function _addHoneypot(&$text, $name)
    {
        $html  = '<div style="display:none !important;">';
        $html .= '    <input type="text" name="'.$name.'" value="" autocomplete="false" tabindex="-1">';
        $html .= '</div>';

        // POST: Add honeyput to the form
        $text  = preg_replace('#(<\s*form[^>]+method="post"[^>]*>)#si',
            '\1'.PHP_EOL.$html,
            $text
        );
    }
}