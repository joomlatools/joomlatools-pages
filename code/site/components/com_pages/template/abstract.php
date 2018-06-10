<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateAbstract extends KTemplate
{
    protected $_filename;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Intercept template exception
        $this->getObject('exception.handler')->addExceptionCallback(array($this, 'handleException'), true);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'filters'   => array('markdown'),
            'functions' => array(
                'data' => function($path, $format = '') {
                    return  $this->getObject('com:pages.data.factory')->createObject($path, $format);
                },
                'collection' => array($this, 'fetchCollection'),
                'date'       => array($this, 'formatDate')
            ),
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types' => array('html', 'txt', 'svg', 'css', 'js'),
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

    public function handleException(Exception &$exception)
    {
        if($exception instanceof KTemplateExceptionError)
        {
            $file   = $exception->getFile();
            $buffer = $exception->getPrevious()->getFile();

            //Get the real file if it can be found
            $line = count(file($file)) - count(file($buffer)) + $exception->getLine() - 1;

            $exception = new KTemplateExceptionError(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getSeverity(),
                $file,
                $line,
                $exception->getPrevious()
            );
        }
    }

    public function fetchCollection($path, $state = array())
    {
        $result = array();
        if($this->getObject('page.registry')->isCollection($path)) {
            $result = $this->getObject('com:pages.model.pages')->setState($state)->path($path)->fetch();
        }

        return $result;
    }
}