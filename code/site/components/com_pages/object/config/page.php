<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesObjectConfigPage extends KObjectConfigYaml
{
    /**
     * The page content
     *
     * @var string
     */
    private $__content = '';

    public function fromString($string, $object = true)
    {
        // Normalize line endings to Unix style.
        $string = preg_replace("/(\r\n|\r)/", "\n", $string);

        if (strpos($string, "---") !== false)
        {
            if(preg_match('#^\s*---(.*|[\s\S]*)\s*---#siU', $string, $matches)) {
                $this->merge(parent::fromString($matches[1], false));
            }

            $this->setContent(str_replace($matches[0], '', $string));
        }
        else $this->setContent($string);

        return $object ? $this : $this->toArray();
    }

    public function toString()
    {
        $string = $this->getContent();

        //Add frontmatter
        if($this->count()) {
            $string = "---\n" . trim(parent::toString()) . "\n---\n\n" . $string;
        }

        // Normalize line endings to Unix style.
        $string = preg_replace("/(\r\n|\r)/", "\n", $string);

        return $string;
    }

    public function getContent()
    {
        return $this->__content;
    }

    public function setContent($content)
    {
        $this->__content = trim($content);
        return $this;
    }
}