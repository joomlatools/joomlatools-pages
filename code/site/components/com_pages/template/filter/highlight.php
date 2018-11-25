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
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'default_language' => 'php',
            'priority' => self::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function filter(&$text)
    {
        if(preg_match_all('#<pre><code\s*([^>]*)>(.*)<\/code></pre>#siU', $text, $matches))
        {
            $highlighter = new Highlight\Highlighter();

            foreach($matches[2] as $key => $code)
            {
                //Create attributes array
                $attributes = array(
                    'language' => $this->getConfig()->default_language,
                );

                $attributes = array_merge($attributes, $this->parseAttributes($matches[1][$key]));

                try
                {
                    $highlighted = $highlighter->highlight($attributes['language'], $code, false);

                    $html  = '<ktml:style src="assets://com_pages/css/highlight.css" />';
                    $html .= '<pre class="hljs ' . $highlighted->language . '">';
                    $html .= htmlspecialchars_decode($highlighted->value, ENT_HTML5);
                    $html .= '</pre>';

                    $text = str_replace($matches[0][$key], $html, $text);
                }
                catch (DomainException $e) {};
            }


        }
    }
}