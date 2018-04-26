<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplate extends KTemplate
{
    protected $_excluded_types;


    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_excluded_types = KObjectConfig::unbox($config->excluded_types);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'excluded_types' => array('html', 'txt', 'svg', 'css', 'js'),
            'functions'  => array(
                'data' => function($path, $format = '') {
                    return  $this->getObject('com:pages.data.factory')->createObject($path, $format);
                },
                'date' => array($this, 'formatDate')

            ),
            'cache'           => false,
            'cache_namespace' => 'pages',
        ));

        parent::_initialize($config);
    }

    protected function formatDate($date, $format = '')
    {
        if(!$date instanceof KDate)
        {
            if(empty($format)) {
                $format = $this->getObject('translator')->translate('DATE_FORMAT_LC3');
            }

            $result = $this->createHelper('date')->format(array('date' => $date, 'format' => $format));
        }
        else $result = $date->format($format);

        return $result;
    }

    public function loadFile($url)
    {
        $locator = $this->getObject('template.locator.factory')->createLocator($url);

        if (!$file = $locator->locate($url)) {
            throw new InvalidArgumentException(sprintf('The template "%s" cannot be located.', $url));
        }

        $type = pathinfo($file, PATHINFO_EXTENSION);

        if(!in_array($type, $this->_excluded_types))
        {
            //Create the template engine
            $config = array(
                'template' => $this,
                'functions' => $this->_functions
            );

            $this->_source = $this->getObject('template.engine.factory')
                ->createEngine($file, $config)
                ->loadFile($url);
        }
        else KTemplateAbstract::loadFile($url);

        return $this;
    }

    public function loadString($source, $type = null, $url = null)
    {
        if($type && !in_array($type, $this->_excluded_types))
        {
            //Create the template engine
            $config = array(
                'template'  => $this,
                'functions' => $this->_functions
            );

            $this->_source = $this->getObject('template.engine.factory')
                ->createEngine($type, $config)
                ->loadString($source,  $url);
        }
        else KTemplateAbstract::loadString($source);

        return $this;
    }
}