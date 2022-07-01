<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityContent extends ComPagesModelEntityItem
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'data' => [
                'slug'        => '',
                'title'       => '',
                'summary'     => '',
                'text'        => '',
                'excerpt'     => null,
                'hash'        => null,
                'date'        => 'now',
                'author'      => null,
                'image'       => [
                    'url' 	   => null,
                    'alt'	   => null,
                    'caption'  => null,
                ],
                'metadata'    => [
                    'og:type'        => 'article',
                    'og:title'       => null,
                    'og:url'         => null,
                    'og:image'       => null,
                    'og:description' => null,
                ],
                'direction'   => 'auto',
                'language'    => 'en-GB',
                'canonical'   => null,
            ],
            'internal_properties' => ['content', 'text'],
        ]);

        parent::_initialize($config);
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

    public function getPropertyMetadata($value)
    {
        $metadata = new ComPagesObjectConfig($value);
        $metadata->append($this->getConfig()->data->metadata);

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

    public function setPropertyImage($value)
    {
        //Normalize images
        $image = null;

        if(!empty($value) && !$value instanceof ComPagesObjectConfig)
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

                $image = new ComPagesObjectConfig($image);
            }


        }
        else $image = new ComPagesObjectConfig($value);

        return $image;
    }

    public function getPropertyDate($value)
    {
        if(!$value instanceof ComKoowaDate)
        {
            //Set the date based on the modified time of the file
            if(is_integer($value)) {
                $date = $this->getObject('date')->setTimestamp($value);
            } else {
                $date = $this->getObject('date', array('date' => trim($value)));
            }
        }
        else $date = $value;

        return $date;
    }

    public function getContent()
    {
        return $this->content;
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