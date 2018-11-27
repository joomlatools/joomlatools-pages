<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterHighlight extends KTemplateFilterAbstract
{
    protected $_highlighter;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Set the markdown compiler
        if($config->highlighter) {
            $this->setHighlighter($config->highlighter);
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'highlighter'      => null,
            'default_language' => 'php',
        ));

        parent::_initialize($config);
    }

    public function filter(&$text)
    {
        if(preg_match_all('#<pre>\s*<code\s*([^>]*)>(.*)<\/code>\s*</pre>#siU', $text, $matches))
        {
            foreach($matches[2] as $key => $code)
            {
                //Create attributes array
                $attributes = array(
                    'language' => $this->getConfig()->default_language,
                );

                $attributes = array_merge($attributes, $this->parseAttributes($matches[1][$key]));

                if($result = $this->_highlight(trim($code), $attributes['language']))
                {
                    $html  = '<ktml:style src="assets://com_pages/css/highlight.css" />';
                    $html .= '<pre class="hljs ' . $attributes['language'] . '">';
                    $html .=  $result;
                    $html .= '</pre>';

                    $text = str_replace($matches[0][$key], $html, $text);
                }
            }
        }
    }

    public function getHighlighter()
    {
        return $this->_highlighter;
    }

    public function setHighlighter(callable $highlighter)
    {
        $this->_highlighter = $highlighter;
        return $this;
    }

    protected function _highlight($source, $language = 'php')
    {
        $result = false;
        if(is_callable($this->_highlighter))
        {
            try
            {
                $result = call_user_func($this->_highlighter, $source, $language);

                //Ensure entities are not encoded when language is not html
                if($language != 'html') {
                    $result = htmlspecialchars_decode($result, ENT_HTML5);
                }
            }
            catch (DomainException $e) {};
        }

        return $result;
    }
}