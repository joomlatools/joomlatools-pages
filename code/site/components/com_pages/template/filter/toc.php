<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */


class ComPagesTemplateFilterToc extends KTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_level' => 1,
            'max_level'  => 6,
            'anchor'     => [
                'enabled'   => true,
                'placement' => 'right',
                'visibale'  => 'hover',
                'icon'      => '&#128279;',
                'class'     => null,
                'truncate'  => null,
                'arialabel' => 'Anchor',
            ],
        ));

        parent::_initialize($config);
    }

    public function filter(&$text)
    {
        $toc = '';

        $matches = array();
        if(preg_match_all('#<ktml:toc\s*([^>]*)>#siU', $text, $matches))
        {
            foreach($matches[0] as $key => $match)
            {
                //Create attributes array
                $attributes = array(
                    'base' => $this->getConfig()->base_level,
                    'max'  => $this->getConfig()->max_level,
                );

                $attributes = array_merge($attributes, $this->parseAttributes($matches[1][$key]));

                $headers = array();
                if(preg_match_all('#<h(['.$attributes['base'].'-'.$attributes['max'].'])\s*[^>]*>(.+?)</h\1>#is', $text, $headers))
                {
                    $toc = '<ul id="toc">';

                    foreach($headers[1] as $key => $level)
                    {
                        $content = $headers[2][$key];
                        $id      = $this->_generateId($content);

                        $result = '<h'.$level.' id="'.$id.'">'.$content.'</h'.$level.'>';
                        $text   = str_replace($headers[0][$key], $result, $text);

                        $toc .= '<li><a href="#'.$id.'">'.$content.'</a>';

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

                    if($this->getConfig()->anchor->enabled) {
                        $toc .= $this->getTemplate()->helper('behavior.anchor', KObjectConfig::unbox($this->getConfig()->anchor));
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