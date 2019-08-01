<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityPage extends ComPagesModelEntityItem
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key'   => 'route',
            'data' => [
                'name'        => '',
                'title'       => '',
                'summary'     => '',
                'slug'        => '',
                'content'     => '',
                'excerpt'     => '',
                'text'        => '',
                'image'       => '',
                'date'        => 'now',
                'author'      => '',
                'published'   => true,
                'category'    => '',
                'access'      => [
                    'roles'  => ['public'],
                    'groups' => ['public', 'guest']
                ],
                'redirect'    => '',
                'metadata'    => [
                    'og:type'        => 'website',
                    'og:title'       => null,
                    'og:url'         => null,
                    'og:image'       => null,
                    'og:description' => null,
                ],
                'process'     => [
                    'filters' => array('meta'),
                ],
                'layout'      => array(),
                'colllection' => false,
                'form'        => false,
                'direction'   => 'auto',
                'canonical'   => null,
            ],
            'internal_properties' => ['process', 'layout', 'format', 'path', 'direction', 'collection', 'form'],
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

    public function getPropertyExcerpt()
    {
        $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->content, 2);

        if(count($parts) > 1) {
            $excerpt = $parts[0];
        } else {
            $excerpt = '';
        }

        return $excerpt;
    }

    public function getPropertyText()
    {
        $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->content, 2);

        if(count($parts) > 1) {
            $text = $parts[1];
        } else {
            $text = $parts[0];
        }

        return $text;
    }

    public function getPropertyRoute()
    {
        $handle = $this->path ? $this->path.'/'.$this->slug : $this->slug;

        //Add the extension
        if($this->format !== 'html') {
            $handle .= '.'.$this->format;
        }

        return $handle;
    }

    public function getPropertyMetadata()
    {
        $metadata = $this->getConfig()->data->metadata;

        if(!isset($metadata->description) && $this->summary) {
            $metadata->set('description', $this->summary);
        }

        //Type and image are required. If they are not set remove any opengraph properties
        if(!empty($metadata->get('og:type')) && !empty($metadata->get('og:image')))
        {
            if($this->title) {
                $metadata->set('og:title', $this->title);
            }

            if($this->summary) {
                $metadata->set('og:description', $this->summary);
            }

            if($this->image) {
                $metadata->set('og:image', $this->image);
            }
        }
        else
        {
            foreach($metadata as $name => $value)
            {
                if(strpos($name, 'og:') === 0 || strpos($name, 'twitter:') === 0) {
                    $metadata->remove($name);
                }
            }
        }

        return $metadata;
    }

    public function setPropertyName($name)
    {
        if(empty($name)) {
            $name = ucwords(str_replace(array('_', '-'), ' ', $this->slug));
        }

        return $name;
    }

    public function setPropertyCategory($category)
    {
        if(empty($category)) {
            $category = basename($this->path);
        }

        return $category;
    }

    public function setPropertyAccess($value)
    {
        return new KObjectConfig($value);
    }

    public function setPropertyProcess($value)
    {
        return new KObjectConfig($value);
    }

    public function setPropertyCollection($value)
    {
        return new KObjectConfig($value);
    }

    public function setPropertyForm($value)
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

    public function setPropertyImage($value)
    {
        if(!empty($value))
        {
            if(is_string($value) && strpos($value, '://') === false) {
                $value = '/'.ltrim($value, '/');
            }

            $image = $this->getObject('lib:http.url')->setUrl($value);
        }
        else $image = null;

        return $image;
    }

    public function setPropertyLayout($value)
    {
        return new KObjectConfig($value);
    }

    public function isCollection()
    {
        return isset($this->collection) && $this->collection !== false ? $this->collection : false;
    }

    public function isForm()
    {
        return isset($this->form) && $this->form !== false ? $this->form : false;
    }

    public function getContent()
    {
        $template = $this->getObject('com:pages.template.default');

        //Load the page
        $template->loadFile('page://pages/'.$this->route);

        //Render page
        return $template->render(KObjectConfig::unbox($template->getData()));
    }

    public function getContentType()
    {
        return 'text/html';
    }

    public function __toString()
    {
        return $this->getContent();
    }
}