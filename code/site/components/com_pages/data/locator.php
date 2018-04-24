<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDataLocator extends KTemplateLocatorFile
{
    protected static $_name = 'data';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_path' =>  Koowa::getInstance()->getRootPath().'/joomlatools-pages/data',
        ));

        parent::_initialize($config);
    }

    public function find(array $info)
    {
        $result = parent::find($info);

        //If the result is a directory, return all the files in the directory
        if($result && is_dir($result)) {
            $result = glob($result.'/*.*');
        }

        return $result;
    }
}