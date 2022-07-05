<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityPage extends ComPagesModelEntityContent
{
    private $__parent;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'data' => [
                'name'    => '',
                'path'    => '',
                'access'  => [],
                'metadata'    => [
                    'og:type'        => 'website',
                    'og:title'       => null,
                    'og:url'         => null,
                    'og:image'       => null,
                    'og:description' => null,
                ],
            ],
            'internal_properties' => ['file'],
        ]);

        parent::_initialize($config);
    }

    public function getPropertyAccess($value)
    {
        return new ComPagesObjectConfig($value);
    }

    public function getPropertyName($name)
    {
        if(empty($name)) {
            $name = ucwords(str_replace(array('_', '-'), ' ', $this->slug));
        }

        return $name;
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
        if($this->getContentType() && !$this->content && $this->file)
        {
            $page = (new ComPagesObjectConfigFrontmatter())->fromFile($this->file);
            $this->content = $page->getContent();
        }

        return $this->content;
    }

    public function getContentType()
    {
        $result = false;

        if($file = $this->file)
        {
            if(str_ends_with($file, '.md')) {
                $result = 'text/markdown';
            }

            if(str_ends_with($file, '.html')) {
                $result = 'text/html';
            }

            if(str_ends_with($file, '.txt')) {
                $result = 'text/plain';
            }
        }

        return $result;
    }

    public function __toString()
    {
        return $this->getContent();
    }
}