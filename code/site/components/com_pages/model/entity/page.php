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
        $config->append([
            'identity_key'   => 'path',
            'data' => [
                'title'       => '',
                'summary'     => '',
                'slug'        => '',
                'content'     => '',
                'excerpt'     => '',
                'date'        => 'now',
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

    public function setPropertyContent($content)
    {
        if(!$this->excerpt)
        {
            $parts = preg_split('#<!--(.*)more(.*)-->#i', $content, 2);
            $this->excerpt = $parts[0];
        }

        return $content;
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