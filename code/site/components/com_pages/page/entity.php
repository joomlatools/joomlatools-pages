<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesPageEntity extends ComPagesModelEntityPage
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
                'colllection' => null,
                'form'        => null,
            ],
        ]);

        parent::_initialize($config);
    }

    public function setPropertyLayout($value)
    {
        return new KObjectConfig($value);
    }

    public function setPropertyCollection($value)
    {
        if($value) {
            $value = new KObjectConfig($value);
        }

        return $value;
    }

    public function getPropertyForm()
    {
        if($form = $this->getConfig()->data->get('form'))
        {
            $name = $form->honeypot ?? 'name';

            if($form->schema->has($name))
            {
                $hash = $this->hash;
                $form->honeypot = sprintf('%s_%s', $name, $hash);
            }
            else $form->honeypot = $name;
        }

        return $form;
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