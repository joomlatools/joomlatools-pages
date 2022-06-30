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
    private $__content;

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
        ]);

        parent::_initialize($config);
    }

    public function getPropertyFolder()
    {
        return dirname(rtrim($this->path, '/'));
    }

    public function setPropertyAccess($value)
    {
        return new ComPagesObjectConfig($value);
    }

    public function setPropertyName($name)
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
        if($this->getContentType() && !$this->content) {
            $this->content = $this->getObject('page.registry')->getPageContent($this->path);
        }

        return $this->content;
    }

    public function getContentType()
    {
        return $this->getObject('page.registry')->getPageContentType($this->path) ?: '';
    }

    public function getHandle()
    {
        return $this->path;
    }

    public function __toString()
    {
        return $this->getContent();
    }
}