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
            'min_level' => 1,
            'max_level' => 6,
            'anchor'     => [
                'enabled'   => true,
                'options' => [
                    'placement' => 'right',
                    'visibale'  => 'hover',
                    'icon'      => "",
                    'class'     => null,
                    'truncate'  => null,
                    'arialabel' => 'Anchor',
                ],
                'selector' => 'article h2, article h3, article h4, article h5, article h6',
            ],
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
        if(preg_match_all('#<h(['.$attributes['min'].'-'.$attributes['max'].'])\s*[^>]*>(.+?)</h\1>#is', $text, $headers))
        {
            foreach($headers[1] as $key => $level)
            {
                $content = $headers[2][$key];
                $id      = $this->_generateId($content);

                $result = '<h'.$level.' id="'.$id.'">'.$content.'</h'.$level.'>';
                $text   = str_replace($headers[0][$key], $result, $text);

                if($this->getConfig()->anchor->enabled) {
                    $text .= $this->getTemplate()->helper('behavior.anchor', KObjectConfig::unbox($this->getConfig()->anchor));
                }
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
                $attributes = array_merge($attributes, $this->parseAttributes($matches[1][$key]));

                $headers = array();
                if(preg_match_all('#<h(['.$attributes['min'].'-'.$attributes['max'].'])\s*[^>]*>(.+?)</h\1>#is', $text, $headers))
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