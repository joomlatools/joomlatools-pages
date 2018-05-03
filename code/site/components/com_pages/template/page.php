<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplatePage extends KTemplate
{
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
            ),
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types'  => array('html', 'txt', 'svg', 'css', 'js'),
        ));

        parent::_initialize($config);
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
}