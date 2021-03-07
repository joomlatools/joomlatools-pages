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
    private $__parent;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'data' => [
                'path'        => '',
                'slug'        => '',
                'name'        => '',
                'title'       => '',
                'summary'     => '',
                'content'     => '',
                'excerpt'     => null,
                'text'        => '',
                'image'       => [
                    //'url' 	   => '',
                    //'alt'	     => null,
                    //'caption'  => null,
                ],
                'date'        => 'now',
                'author'      => null,
                'access'      => [],
                'metadata'    => [
                    'og:type'        => 'website',
                    'og:title'       => null,
                    'og:url'         => null,
                    'og:image'       => null,
                    'og:description' => null,
                ],
                'direction'   => 'auto',
                'language'    => 'en-GB',
                'canonical'   => null,
            ],
            'internal_properties' => [
                'format',
                'route',
                'slug',
                'path',
                'folder',
                'content',
                'hash',
                'text',
                'excerpt',
            ],
        ]);

        parent::_initialize($config);
    }

    public function getPropertyFolder()
    {
        return dirname($this->path);
    }

    public function getPropertyExcerpt()
    {
        $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->getContent(), 2);

        if(count($parts) > 1) {
            $excerpt = $parts[0];
        } else {
            $excerpt = null;
        }

        return $excerpt;
    }

    public function getPropertyText()
    {
        $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->getContent(), 2);

        if(count($parts) > 1) {
            $text = $parts[1];
        } else {
            $text = $parts[0];
        }

        return $text;
    }

    public function getPropertyMetadata()
    {
        $metadata = $this->getConfig()->data->metadata;

        if(!isset($metadata->description) && $this->summary) {
            $metadata->set('description', $this->summary);
        }

        if($this->image && $this->image->url) {
            $metadata->set('og:image', $this->image->url);
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

            if($this->language) {
                $metadata->set('og:locale', $this->language);
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

    public function setPropertyAccess($value)
    {
        return new ComPagesObjectConfig($value);
    }

    public function setPropertyProcess($value)
    {
        return new ComPagesObjectConfig($value);
    }

    public function setPropertyCollection($value)
    {
        return new ComPagesObjectConfig($value);
    }

    public function setPropertyForm($value)
    {
        return new ComPagesObjectConfig($value);
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
        //Normalize images
        $image = null;

        if(!empty($value))
        {
            if(is_array($value)) {
                $url = $value['url'] ?? '';
            } else {
                $url = $value;
            }

            if($url)
            {
                if(is_string($url) && strpos($url, '://') === false) {
                    $url = '/'.ltrim($url, '/');
                }

                $url = $this->getObject('lib:http.url')->setUrl($url);

                $image = [
                    'url'      => $url,
                    'alt'      => $value['alt'] ?? null,
                    'caption'  => $value['caption'] ?? null,
                ];
            }

            $image = new ComPagesObjectConfig($image);
        }

        return $image;
    }

    public function getParent()
    {
        if(!$this->__parent)
        {
            $page = $this->getObject('page.registry')->getPage($this->folder);

            $this->__parent = $this->getObject($this->getIdentifier(),
                array('data'  => $page->toArray())
            );
        }

        return $this->__parent;
    }

    public function getContent()
    {
        if(!$this->content) {
            $this->content = $this->getObject('page.registry')->getPageContent($this->path);
        }

        return $this->content;
    }

    public function getContentType()
    {
        return 'text/html';
    }

    public function getHandle()
    {
        return $this->path;
    }

    public function __toString()
    {
        return $this->getObject('page.registry')->getPageContent($this->path, true);
    }
}