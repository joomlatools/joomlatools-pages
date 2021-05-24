<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateEngineMarkdown extends KTemplateEngineMarkdown
{
    protected function _compile($source)
    {
        $source = preg_replace('#\s*---(.*|[\s\S]*)\s*---#siU', '', $source);
        return parent::_compile($source);
    }
}