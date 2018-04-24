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
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the config object
        $config = (new ComPagesObjectConfigPage())->fromFile($this->file);

        //Set the properties
        $this->setProperties($config->toArray(), false);

        //Se the content
        $this->_content = $config->getContent();
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
        static $collection;

        if($this->collection !== false)
        {
            $collection = $this->getObject('com:pages.controller.page')
                ->path($this->path)
                ->browse();

            return $collection;
        }

        return array();
    }

    public function content($refresh = false)
    {
        static $content;

        if(!isset($content) || $refresh)
        {
            $config = array('functions' => array(
                'collection' => array($this, 'collection'),
                'excerpt'    => array($this, 'excerpt')
            ));

            $type    = pathinfo($this->file, PATHINFO_EXTENSION);
            $content = $this->getObject('com:pages.template', $config)
                ->loadString($this->_content, $type != 'html' ? $type : null, $this->path)
                ->render($this->getProperties());
        }

        return $content;
    }

    public function excerpt($refresh = false)
    {
        static $excerpt;

        if(!isset($excerpt) || $refresh)
        {
            $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->content(), 2);
            $excerpt = $parts[0];
        }

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