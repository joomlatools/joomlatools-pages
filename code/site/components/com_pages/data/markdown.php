<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDataMarkdown extends ComPagesObjectConfigFrontmatter
{
    public function setContent($content)
    {
        $this->content = \Michelf\MarkdownExtra::defaultTransform($content);
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }
}