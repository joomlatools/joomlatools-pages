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
                //Attributes
                'redirect'    => '',
                'process'     => [
                    'filters' => [],
                ],
                'layout'     => array(),
                'collection' => null,
                'form'       => null,
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
       if(is_array($value)) {
            $value = new ComPagesObjectConfig($value);
        }
        return $value;
    }

    public function setPropertyCollection($value)
    {
        if(is_array($value)) {
            $value = new ComPagesObjectConfig($value);
        }

        return $value;
    }

    public function setPropertyForm($value)
    {
        if($value)
        {
            if(is_array($value)) {
                $value = new ComPagesObjectConfig($value);
            }

            $name = $value->honeypot ?? 'name';

            if($value->schema->has($name))
            {
                $hash = $this->hash;
                $value->honeypot = sprintf('%s_%s', $name, $hash);
            }
            else $value->honeypot = $name;
        }

        return $value;
    }

    public function setPropertyProcess($value)
    {
        if(is_array($value)) {
            $value = new ComPagesObjectConfig($value);
        }

        return $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isRedirect()
    {
        return $this->getType() == 'redirect';
    }

    public function isForm()
    {
        return $this->getType() == 'form' ?  $this->form : false;
    }

    public function isCollection()
    {
        return $this->getType() == 'collection' ? $this->collection : false;
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
