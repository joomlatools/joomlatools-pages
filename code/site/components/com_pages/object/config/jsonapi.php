<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesObjectConfigJsonapi extends KObjectConfigJson
{
    protected static $_media_type = 'application/vnd.api+json';

    public function fromString($string, $object = true)
    {
        $data = parent::fromString($string, false);
        $result = array();
        if(isset($data['data']))
        {
            $data = $data['data'];

            //Collection
            if(is_numeric(key($data)))
            {
                foreach($data as $key => $item) {
                    $result[$key] = ['id' => $item['id']] +  $item['attributes'];
                }
            }
            //Resource
            else $result = ['id' => $data['id']] +  $data['attributes'];
        }

        return $object ? $this->merge($result) : $result;
    }
}