<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesTemplateLocatorData extends KTemplateLocatorFile
{
    /**
     * The locator name
     *
     * @var string
     */
    protected static $_name = 'data';

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  KObjectConfig $config  An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_path' =>  Koowa::getInstance()->getRootPath().'/joomlatools-pages/data',
        ));

        parent::_initialize($config);
    }

    /**
     * Find the data file path(s)
     *
     * @param array  $info  The path information
     * @return string|array|false The real data file paths(s) or FALSE if the template could not be found
     */
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