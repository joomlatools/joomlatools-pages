<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterRoute extends KTemplateFilterAbstract
{
    /**
     * Convert the schemes to their real paths
     *
     * @param string $text  The text to parse
     * @return void
     */
    public function filter(&$text)
    {
        preg_match_all('#route://([^"]+)#m', $text, $matches);

        foreach (array_unique($matches[1]) as $key => $query)
        {
            $text = str_replace(
                $matches[0][$key],
                $this->getTemplate()->route($query),
                $text
            );
        }
    }
}