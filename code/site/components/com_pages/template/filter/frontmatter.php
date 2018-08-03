<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterFrontmatter extends KTemplateFilterAbstract
{
    public function filter(&$text)
    {
        if (strpos($text, "---") !== false)
        {
            if(preg_match('#^\s*---(.*|[\s\S]*)\s*---#siU', $text, $matches))
            {
                foreach($matches as $match) {
                    $text = str_replace($matches, '', $text);
                }
            }
        }
    }
}
