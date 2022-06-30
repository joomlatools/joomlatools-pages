<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesObjectConfigMarkdown extends ComPagesObjectConfigFrontmatter
{
    protected static $_media_type = 'text/markdown';

    public function getContent($markdown = true)
    {
        $content = parent::getContent();
        return $markdown ? \Michelf\MarkdownExtra::defaultTransform($content) : $content;
    }
}