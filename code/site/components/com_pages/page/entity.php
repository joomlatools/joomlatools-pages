<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesPageEntity extends ComPagesModelEntityPage implements ComPagesPageInterface
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'data' => [
                'redirect'    => '',
                'process'     => [
                    'filters' => [],
                ],
                'layout'      => array(),
                'collection' => null,
                'form'        => null,
            ],
        ]);

        parent::_initialize($config);
    }

    public function get($name, $default = null)
    {
        if(!is_array($name)) {
            $segments = explode('/', $name);
        } else {
            $segments = $name;
        }

        $result = parent::get(array_shift($segments), $default);

        if(!empty($segments) && $result instanceof KObjectConfigInterface) {
            $result = $result->get($segments, $default);
        }

        return $result;
    }

    public function has($name)
    {
        return (bool) $this->get($name);
    }

    public function setPropertyLayout($value)
    {
        if($value) {
            $value = new ComPagesObjectConfig($value);
        }

        return $value;
    }

    public function setPropertyCollection($value)
    {
        if($value) {
            $value = new ComPagesObjectConfig($value);
        }

        return $value;
    }

    public function setPropertyForm($value)
    {
        if($value) {
            $value = new ComPagesObjectConfig($value);
        }

        return $value;
    }

    public function setPropertyProcess($value)
    {
        if($value) {
            $value = new ComPagesObjectConfig($value);
        }

        return $value;
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
        return $this->redirect ? $this->redirect : false;
    }

    public function isCollection()
    {
        return $this->collection ? $this->collection : false;
    }

    public function isForm()
    {
        return $this->form ? $this->form : false;
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
