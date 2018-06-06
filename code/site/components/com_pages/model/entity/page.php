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
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'identity_key'   => 'path',
            'data' => array(
                'title'       => '',
                'summary'     => '',
                'alias'       => '',
                'content'     => '',
                'excerpt'     => '',
                'date'        => 'now',
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

    public function setPropertyExcerpt($excerpt)
    {
        if(empty($excerpt))
        {
            $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->content, 2);
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
        }

        $data['content']  = $this->content;
        $data['access']   = $this->access->toArray();
        $data['metadata'] = $this->metadata->toArray();
        $data['date']     = $this->date->format(DateTime::ATOM);

        return $data;
    }
}