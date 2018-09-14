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
        $config->append([
            'filters'   => ['markdown', 'frontmatter', 'asset'],
            'functions' => [
                'date'       => [$this, 'formatDate'],
                'data'       => [$this, 'fetchData'],
                'page'       => [$this, 'fetchPage'],
                'pages'      => [$this, 'fetchPages'],
                'slug'       => [$this, 'createSlug'],
                'attribute'  => [$this, 'createAttribute'],
            ],
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types' => ['html', 'txt', 'svg', 'css', 'js'],
        ]);

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

    protected function createSlug($string)
    {
        return $this->getObject('filter.factory')->createFilter('slug')->sanitize($string);
    }

    protected function createAttribute($name, $value)
    {
        $result = '';

        if($value)
        {
            if(is_array($value)) {
                $value = implode(' ', $value);
            }

            $result = $name.'="'.$value.'"';
        }

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

    public function fetchData($path, $format = '')
    {
        $result = false;
        if(is_array($path))
        {
            foreach($path as $directory)
            {
                if (!$result instanceof ComPagesDataObject) {
                    $result = $this->getObject('data.registry')->getData($directory, $format);
                } else {
                    $result->append($this->getObject('data.registry')->getData($directory, $format));
                }
            }
        }
        else $result = $this->getObject('data.registry')->getData($path, $format);

        return $result;
    }

    public function fetchPage($path)
    {
        $result = array();
        if($this->getObject('page.registry')->isPage($path))
        {
            $result = $this->getObject('com:pages.model.pages')
                ->path($path)
                ->fetch();
        }

        return $result;
    }

    public function fetchPages($path = '.', $state = array())
    {
        $result = array();

        if ($this->getObject('page.registry')->isPage($path))
        {
            if(is_string($state)) {
                $state = json_decode('{'.preg_replace('/(\w+)/', '"$1"', $state).'}', true);
            }

            $result = $this->getObject('com:pages.model.pages')
                ->setState($state)
                ->path($path)
                ->fetch();
        }

        return $result;
    }
}