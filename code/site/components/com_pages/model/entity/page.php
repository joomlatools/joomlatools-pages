<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityPage extends KModelEntityAbstract
{
    protected $_content = '';

    protected $_content_rendered = false;

    protected $_collection_object = false;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the config object
        $page = (new ComPagesObjectConfigPage())->fromFile($this->file);

        //Set the properties
        $this->setProperties($page->toArray(), false);

        //Set the content
        $this->_content = $page->getContent();
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'identity_key'   => 'page',
            'data' => array(
                'title'       => '',
                'summary'     => '',
                'date'        => '',
                'published'   => true,
                'access'      => array(
                    'roles'  => array('public'),
                    'groups' => array('public', 'guest')
                ),
                'redirect'    => '',
                'metadata'    => array(),
                'process'     => array(
                    'plugins' => true
                ),
                'layout'      => '',
                'colllection' => false,
            ),
        ));

        parent::_initialize($config);
    }

    public function collection()
    {
        if($this->collection !== false)
        {
            if(!$this->_collection_object)
            {
                $this->_collection_object = $this->getObject('com:pages.controller.page')
                    ->path($this->path)
                    ->browse();
            }

            return  $this->_collection_object;
        }

        return array();
    }

    public function content($refresh = false)
    {
        if(!$this->_content_rendered || $refresh)
        {
            $config = array('functions' => array(
                'collection' => array($this, 'collection'),
                'excerpt'    => array($this, 'excerpt')
            ));

            $content = $this->getObject('com:pages.template.page', $config)
                ->loadString($this->_content,  pathinfo($this->file, PATHINFO_EXTENSION), $this->url)
                ->render($this->getProperties());

            $this->_content_rendered = $content;
        }

        return $this->_content_rendered;
    }

    public function excerpt($refresh = false)
    {
        $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->content($refresh), 2);
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

    public function setPropertyMetadata($value)
    {
        $config = new KObjectConfig($value);

        //Set the summary as the metadata descriptipn
        if(!isset($config->description)) {
            $config->description = $this->summary;
        }

        return $config;
    }

    public function setPropertyDate($value)
    {
        //Set the date based on the modified time of the file
        if(empty($value)) {
            $date = $this->getObject('date')->setTimestamp(filemtime($this->file));
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
        }

        $data['content']  = $this->content();
        $data['excerpt']  = $this->excerpt();
        $data['access']   = $this->access->toArray();
        $data['metadata'] = $this->metadata->toArray();
        $data['date']     = $this->date->format(DateTime::ATOM);

        unset($data['file']);
        unset($data['path']);

        return $data;
    }
}