<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterHighlight extends ComPagesTemplateFilterAbstract
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
            'debug'            =>  JDEBUG ? true : false,
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
                    'class' => $this->getConfig()->default_language,
                );

                $attributes = array_merge($attributes, $this->parseAttributes($matches[1][$key]));

                //Ensure entities are not encoded when passing to the highlighter.
                $code = htmlspecialchars_decode($code, ENT_HTML5);

                if($result = $this->_highlight(trim($code), $attributes['class']))
                {
                    $html = '<ktml:style src="assets://com_pages/css/highlight-v1.8.4.'.($this->getConfig()->debug ? 'min.css' : 'css').'" />';
                    $html .= '<pre><code class="hljs ' . $attributes['class'] . '">';
                    $html .=  $result;
                    $html .= '</code></pre>';

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
            try {
                $result = call_user_func($this->_highlighter, $source, $language);
            }
            catch (DomainException $e) {};
        }

        return $result;
    }
}