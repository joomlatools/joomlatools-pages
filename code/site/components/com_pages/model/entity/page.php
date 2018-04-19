<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
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
                    'plugins' => false
                ),
                'layout'      => 'default'
            ),
        ));

        parent::_initialize($config);
    }

    public function content($refresh = false)
    {
        static $content;

        if(!isset($content) || $refresh)
        {
            $type    = pathinfo($this->file, PATHINFO_EXTENSION);
            $content = $this->getObject('com:pages.template')
                ->loadString($this->_content, $type != 'html' ? $type : null, $this->path)
                ->render($this->getProperties());

            //Run page content through content plugins
            if($this->process->plugins)
            {
                $content = JHtml::_('content.prepare', $content);

                // Make sure our script filter does not screw up email cloaking
                if (strpos($content, '<script') !== false) {
                    $content = str_replace('<script', '<script data-inline', $content);
                }
            }
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
            $date = $this->getObject('date', array('date' => $value));
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