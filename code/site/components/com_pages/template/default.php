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

    private $__helpers = array();

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Intercept template exception
        $this->getObject('exception.handler')->addExceptionCallback(array($this, 'handleException'), true);

        //Re-register the helper() template function
        $this->registerFunction('helper', [$this, 'invokeHelper']);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'filters'   => ['frontmatter', 'partial'],
            'functions' => [
                'date'       => [$this, '_formatDate'],
                'data'       => [$this, '_fetchData'],
                'slug'       => [$this, '_createSlug'],
                'attributes' => [$this, '_createAttributes'],
            ],
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types' => ['html', 'txt', 'svg', 'css', 'js'],
        ]);

        parent::_initialize($config);
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

    public function invokeHelper($identifier, ...$params)
    {
        //Get the function and helper based on the identifier
        if(strpos($identifier, '.') === false) {
            $identifier = $identifier.'.__invoke';
        }

        //Get the function and helper based on the identifier
        $parts      = explode('.', $identifier);
        $function   = array_pop($parts);
        $identifier = array_pop($parts);

        //Handle schema:package.helper.function identifiers
        if(!empty($parts)) {
            $identifier = implode('.', $parts).'.template.helper.'.$identifier;
        }
        
        $helper = $this->createHelper($identifier);

        //Call the helper function
        if (!is_callable(array($helper, $function))) {
            throw new BadMethodCallException(get_class($helper) . '::' . $function . ' not supported.');
        }

        //Merge the parameters if helper asks for it
        if ($helper instanceof KTemplateHelperParameterizable) {
            $params = array_merge($this->getParameters()->toArray(), $params);
        }

        if(is_numeric(key($params))) {
            $result = $helper->$function(...$params);
        } else {
            $result = $helper->$function($params);
        }

        return $result;
    }

    public function createHelper($helper, $config = array())
    {
        //Create the complete extension identifier if a partial identifier was passed
        if (is_string($helper) && strpos($helper, ':') === false)
        {
            $identifier = 'ext:template.helper.'.$helper;

            //Create the template helper
            if($this->getObject('manager')->getClass($identifier)) {
                $helper = $identifier;
            }
         }

        if(!isset($this->__helpers[$helper])) {
            $this->__helpers[$helper] = parent::createHelper($helper, $config);
        }

        return $this->__helpers[$helper];
    }

    public function registerFunction($name, $function)
    {
        if (!is_callable($function))
        {
            if(file_exists($function)) {
                $function = include $function;
            }
        }

        return parent::registerFunction($name, $function);
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

    protected function _createAttributes($name, $value = null)
    {
        $result = '';

        if(!is_array($name) && $value) {
            $name = array($name => $value);
        }

        if($name instanceof KObjectConfig) {
            $name = KObjectConfig::unbox($name);
        }

        if(is_array($name))
        {
            $output = array();
            foreach($name as $key => $item)
            {
                if(is_array($item))
                {
                    foreach($item as $k => $v)
                    {
                        if(empty($v)) {
                            unset($item[$k]);
                        }
                    }

                    $item = implode(' ', $item);
                }

                if (is_bool($item))
                {
                    if ($item === false) continue;
                    $item = $key;
                }

                $output[] = $key.'="'.str_replace('"', '&quot;', $item).'"';
            }

            $result = ' '.implode(' ', $output);
        }

        return $result;
    }

    protected function _fetchData($path)
    {
        $result = false;
        if(is_array($path))
        {
            if(is_numeric(key($path)))
            {
                foreach($path as $directory)
                {
                    if (!$result instanceof ComPagesDataObject) {
                        $result = $this->getObject('data.registry')->getData($directory);
                    } else {
                        $result->append($this->getObject('data.registry')->getData($directory));
                    }
                }
            }
            else
            {
                $class = $this->getObject('manager')->getClass('com://site/pages.data.object');
                $result = new $class($path);
            }

        }
        else $result = $this->getObject('data.registry')->getData($path);

        return $result;
    }
}