<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesObjectConfigFrontmatter extends KObjectConfigYaml
{
    private $__content = '';

    public function fromFile($filename, $object = true)
    {
        //Store the filename
        $this->filename = $filename;

        return parent::fromFile($filename, $object);
    }

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

    public function getFilename()
    {
        return $this->filename;
    }

    public function getFiletype()
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }
}