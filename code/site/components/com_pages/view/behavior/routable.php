<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewBehaviorRoutable extends KViewBehaviorAbstract
{
    public function _afterRender(KViewContextInterface $context)
    {
        $text = $context->result;

        preg_match_all('#route://([^"]+)#m', $text, $matches);

        foreach (array_unique($matches[1]) as $key => $query)
        {
            $text = str_replace(
                $matches[0][$key],
                $this->getTemplate()->route($query),
                $text
            );
        }

        $context->result = $text;
    }
}