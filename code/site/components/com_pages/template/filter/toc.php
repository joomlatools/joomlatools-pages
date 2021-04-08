<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterToc extends ComPagesTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'min_level' => 2,
            'max_level' => 6,
            'anchor'     => true,
            'icon'       => '#'
        ));

        parent::_initialize($config);
    }

    public function filter(&$text)
    {
        $toc = '';

        //Create attributes array
        $attributes = array(
            'min' => $this->getConfig()->min_level,
            'max' => $this->getConfig()->max_level,
        );

        /*
         * Headings
         */

        $matches = array();
        if($this->isEnabled())
        {
            preg_match_all('#<h(['.$attributes['min'].'-'.$attributes['max'].'])\s*[^>]*>(.+?)</h\1>#is', $text, $headers);

            foreach($headers[1] as $key => $level)
            {
                $content = $headers[2][$key];
                $id      = $this->_generateId($content);

                $result = '<h'.$level.' id="'.$id.'" class="toc-anchor"><a href="#'.$id.'">'.$content.'</a></h'.$level.'>';
                $text   = str_replace($headers[0][$key], $result, $text);

                if($this->getConfig()->anchor)
                {
                    $icon = $this->getConfig()->icon;

                    //Accessible anchor links, see https://codepen.io/johanjanssens/pen/PoWObpL
                    $text .= <<<ANCHOR
<style>
@media (hover: hover) {
  .toc-anchor  {
    margin-left: -1em;
    padding-left: 1em;
  }
  .toc-anchor a {
    text-decoration: none;
    pointer-events: none;
    color: inherit !important;
  }
  .toc-anchor a::after  {
    content: '$icon';
    font-size: 0.8em;
    padding-left: .3em; /* to make the content a bigger target */
    pointer-events: auto;
    visibility: hidden;
    display: inline-block;
  }
  .toc-anchor:hover a::after {
    visibility: visible;
  }
}
</style>
ANCHOR;

                }
            }

            /*
             * Table of content
            */
            $matches = array();
            if(preg_match_all('#<ktml:toc\s*([^>]*)>#siU', $text, $matches))
            {
                foreach($matches[0] as $key => $match)
                {
                    $toc = '';
                    $attributes = array_merge($attributes, $this->parseAttributes($matches[1][$key]));

                    if($headers)
                    {
                        $toc = '<ul class="toc" itemscope itemtype="http://www.schema.org/SiteNavigationElement">';

                        foreach($headers[1] as $key => $level)
                        {
                            $content = $headers[2][$key];
                            $id      = $this->_generateId($content);

                            $toc .= '<li><a href="#'.$id.'" title="'.$content.'" itemprop="url"><span itemprop="name">'.$content.'</span></a>';

                            if(isset($headers[1][$key + 1])) {
                                $next = $headers[1][$key + 1];
                            } else {
                                $next = $headers[1][0];
                            }

                            if($next > $level) {
                                $toc .= '<ul>';
                            }

                            if($next < $level) {
                                $toc .= str_repeat('</li></ul>', $level - $next);
                            }

                            if($next == $level) {
                                $toc .= '</li>';
                            }
                        }

                        $toc .= '</ul>';
                    }

                }

                //Remove the <khtml:toc> tags
                $text = str_replace($match, $toc, $text);
            }
        }
    }

    protected function _generateId($text)
    {
        // Lowercase the string and convert a few characters.
        $id = strtr(strtolower($text), array(' ' => '-', '_' => '-', '[' => '-', ']' => ''));

        // Remove invalid id characters.
        $id = preg_replace('/[^A-Za-z0-9\-_]/', '', $id);

        return $id;
    }
}