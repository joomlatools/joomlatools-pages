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
    public function get($name, $default = null)
    {
        if(!is_array($name)) {
            $segments = explode('/', $name);
        } else {
            $segments = $name;
        }

        $result = parent::get(array_shift($segments), $default);

        if(!empty($segments) && $result instanceof ComPagesPageObject) {
            $result = $result->get($segments, $default);
        }

        return $result;
    }

    public function has($name)
    {
        return (bool) $this->get($name);
    }

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

        if($this->isRedirect()) {
            $type = 'redirect';
        }

        return $type;
    }

    public function isRedirect()
    {
        return isset($this->redirect) && $this->redirect !== false ? KObjectConfig::unbox($this->redirect) : false;
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

    public function __debugInfo()
    {
        return self::unbox($this);
    }
}