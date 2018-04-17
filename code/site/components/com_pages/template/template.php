<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesTemplate extends KTemplate
{
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
            'functions'  => array(
                'data' => function($path, $format = '') {
                    return  $this->getObject('com:pages.data.factory')->createObject($path, $format);
                }

            ),
            'cache'           => false,
            'cache_namespace' => 'pages',
        ));

        parent::_initialize($config);
    }
}