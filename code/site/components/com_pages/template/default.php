<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateDefault extends KTemplate
{
    protected $_layout;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Intercept template exception
        $this->getObject('exception.handler')->addExceptionCallback(array($this, 'handleException'), true);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'filters'   => ['frontmatter', 'partial'],
            'functions' => [
                'date'       => [$this, '_formatDate'],
                'data'       => [$this, '_fetchData'],
                'slug'       => [$this, '_createSlug'],
                'attribute'  => [$this, '_createAttribute'],
                'attributes' => [$this, '_createAttributes']
            ],
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types' => ['html', 'txt', 'svg', 'css', 'js'],
        ]);

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

    public function addFilters($filters)
    {
        foreach((array)KObjectConfig::unbox($filters) as $key => $value)
        {
            if (is_numeric($key)) {
                $this->addFilter($value);
            } else {
                $this->addFilter($key, $value);
            }
        }

        return $this;
    }

    public function loadFile($url)
    {
        //Qualify the layout
        if(!parse_url($url, PHP_URL_SCHEME)) {
            $url = 'page://layouts/'.$url;
        }

        if(parse_url($url, PHP_URL_SCHEME) == 'page')
        {
            if(!$file = $this->getObject('template.locator.factory')->locate($url)) {
                throw new RuntimeException(sprintf('Cannot find layout: "%s"', $url));
            }

            //Load the layout
            $template = (new ComPagesObjectConfigFrontmatter())->fromFile($file);

            if(isset($template->page) || isset($template->pages)) {
                throw new KTemplateExceptionSyntaxError('Using "page or pages" in layout frontmatter is not allowed');
            }

            //Set the parent layout
            if($layout = KObjectConfig::unbox($template->layout))
            {
                if(is_array($layout)) {
                    $this->_layout = $layout['path'];
                } else {
                    $this->_layout = $layout;
                }
            }
            else $this->_layout = false;

            //Store the data and remove the layout
            $this->_data = KObjectConfig::unbox($template->remove('layout'));

            //Load the content
            $result = $this->loadString($template->getContent(), pathinfo($file, PATHINFO_EXTENSION), $url);
        }
        else $result = parent::loadFile($url);

        return $result;
    }

    public function getLayout()
    {
        return $this->_layout;
    }

    public function render(array $data = array())
    {
        unset($data['layout']);

        return parent::render($data);
    }

    protected function _formatDate($date, $format = '')
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

    protected function _createSlug($string)
    {
        return $this->getObject('filter.factory')->createFilter('slug')->sanitize($string);
    }

    protected function _createAttribute($name, $value)
    {
        $result = '';

        if($value)
        {
            if(is_array($value)) {
                $value = implode(' ', $value);
            }

            $result = ' '.$name.'="'.$value.'"';
        }

        return $result;
    }

    protected function _createAttributes($array)
    {
        $output = array();

        if($array instanceof KObjectConfig) {
            $array = KObjectConfig::unbox($array);
        }

        if(is_array($array))
        {
            foreach($array as $key => $item)
            {
                if(is_array($item)) {
                    $item = implode(' ', $item);
                }

                if (is_bool($item))
                {
                    if ($item === false) continue;
                    $item = $key;
                }

                $output[] = $key.'="'.str_replace('"', '&quot;', $item).'"';
            }
        }

        return implode(' ', $output);
    }

    protected function _fetchData($path, $format = '')
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
}