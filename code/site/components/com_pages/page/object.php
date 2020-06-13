<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesPageObject extends ComPagesObjectConfigFrontmatter implements ComPagesPageInterface
{
    public function getType()
    {
        $type = 'page';

        if($this->isCollection()) {
            $type = 'collection';
        }

        if($this->isForm()) {
            $type = 'form';
        }

        if($this->isDecorator()) {
            $type = 'decorator';
        }

        return $type;
    }

    public function isForm()
    {
        return isset($this->form) && $this->form !== false ? KObjectConfig::unbox($this->form) : false;
    }

    public function isCollection()
    {
        return isset($this->collection) && $this->collection !== false ? KObjectConfig::unbox($this->collection) : false;
    }

    public function isDecorator()
    {
        return (bool) $this->process->get('decorate', false) !== false;
    }

    public function isSubmittable()
    {
        return $this->isForm() && isset($this->form->schema);
    }

    public function isEditable()
    {
        return $this->isCollection() && isset($this->collection->schema);
    }
}