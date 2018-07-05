<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityPage extends KModelEntityAbstract implements JsonSerializable
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key'   => 'path',
            'data' => [
                'title'       => '',
                'summary'     => '',
                'slug'        => '',
                'content'     => '',
                'excerpt'     => '',
                'date'        => 'now',
                'author'      => '',
                'published'   => true,
                'access'      => [
                    'roles'  => ['public'],
                    'groups' => ['public', 'guest']
                ],
                'redirect'    => '',
                'metadata'    => [],
                'process'     => [
                    'plugins' => true
                ],
                'layout'      => '',
                'colllection' => false,
            ],
        ]);

        parent::_initialize($config);
    }

    public function get($property, $default)
    {
        if($this->hasProperty($property)) {
            $result = $this->getProperty($property);
        } else {
            $result = $default;
        }

        return $result;
    }

    public function getPropertyDay()
    {
        return $this->date->format('d');
    }

    public function getPropertyMonth()
    {
        return $this->date->format('m');
    }

    public function getPropertyYear()
    {
        return $this->date->format('y');
    }

    public function getPropertyContent()
    {
        $registry = $this->getObject('page.registry');
        $content = $registry->getPage($this->path.'/'.$this->slug)->content;

        return $content;
    }

    public function getPropertyExcerpt()
    {
        $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->content, 2);
        $excerpt = $parts[0];

        return $excerpt;
    }

    public function setPropertyAccess($value)
    {
        return new KObjectConfig($value);
    }

    public function setPropertyProcess($value)
    {
        return new KObjectConfig($value);
    }

    public function setPropertyDate($value)
    {
        //Set the date based on the modified time of the file
        if(is_integer($value)) {
            $date = $this->getObject('date')->setTimestamp($value);
        } else {
            $date = $this->getObject('date', array('date' => trim($value)));
        }

        return $date;
    }

    public function toArray()
    {
        $data = parent::toArray();

        foreach($data as $key => $value)
        {
            if(empty($value)) {
                unset($data[$key]);
            }

            if($value instanceof KObjectConfigInterface) {
                $data[$key] = KObjectConfig::unbox($value);
            }

            if($value instanceof KDate) {
                $data[$key] = $value->format(DateTime::ATOM);
            }

        }

        return $data;
    }

    public function getHandle()
    {
        $handle = $this->path ? $this->path.'/'.$this->slug : $this->slug;
        return $handle;
    }

    public function jsonSerialize()
    {
        $data = $this->toArray();

        unset($data['process']);
        unset($data['layout']);
        unset($data['path']);

        return $data;
    }

    public function isCollection()
    {
        return isset($this->collection) && $this->collection !== false ? $this->collection : false;
    }

    public function __call($method, $arguments)
    {
        $parts = KStringInflector::explode($method);

        //Check if a behavior is mixed
        if ($parts[0] == 'is' && isset($parts[1]))
        {
            if(!isset($this->_mixed_methods[$method])) {
                return false;
            }
        }

        return parent::__call($method, $arguments);
    }
}