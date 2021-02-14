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
    protected $_layout_data;

    protected $_type;

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
            'filters'   => ['partial'],
            'functions' => [
                'date'       => [$this, '_formatDate'],
                'data'       => [$this, '_fetchData'],
                'slug'       => [$this, '_createSlug'],
                'attributes' => [$this, '_createAttributes'],
                'config'     => [$this, '_getConfig'],
                'unbox'      => [$this, '_unboxObject']
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
            //Locate the template
            if(!$file = $this->getObject('template.locator.factory')->locate($url)) {
                throw new RuntimeException(sprintf('Cannot find layout: "%s"', $url));
            }

            //Load the template
            $template = (new ComPagesObjectConfigFrontmatter())->fromFile($file);

            //Set the parent layout
            if($layout = KObjectConfig::unbox($template->layout))
            {
                if(is_array($layout))
                {
                    $this->_layout      = $layout['path'];
                    $this->_layout_data = $layout;

                    unset($this->_layout_data['path']);
                }
                else $this->_layout = $layout;
            }
            else $this->_layout = false;

            //Store the data and remove the layout
            $this->_data = KObjectConfig::unbox($template->remove('layout'));

            //Store the type
            $this->_type = pathinfo($file, PATHINFO_EXTENSION);

            if(!in_array($this->_type, $this->_excluded_types))
            {
                //Create the template engine
                $config = array(
                    'template'  => $this,
                    'functions' => $this->_functions
                );

                $this->_source = $this->getObject('template.engine.factory')->createEngine($this->_type, $config);

                if($cache = $this->_source->isCached(crc32($url)))
                {
                    if($this->_source->getConfig()->cache_reload && filemtime($cache) < filemtime($file)) {
                        unlink($cache);
                    }
                }

                $this->_source->loadString($template->getContent(),  $url);
            }
            else $this->_source = $template->getContent();
        }
        else $result = parent::loadFile($url);

        return $this;
    }

    public function getLayout()
    {
        return $this->_layout;
    }

    public function getLayoutData()
    {
        return new KObjectConfigJson($this->_layout_data);
    }

    public function render(array $data = array())
    {
        unset($data['layout']);

        $result = parent::render($data);

        //Exception for html files
        if($this->_type == 'html') {
            $result = $this->filter();
        }

        return $result;
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

    protected function _fetchData($path, $cache = true)
    {
        $result = false;
        if(is_array($path))
        {
            if(is_numeric(key($path)))
            {
                foreach($path as $directory)
                {
                    if (!$result instanceof ComPagesDataObject) {
                        $result = $this->getObject('data.registry')->fromPath($directory);
                    } else {
                        $result->append($this->getObject('data.registry')->fromPath($directory));
                    }
                }
            }
            else
            {
                $class = $this->getObject('manager')->getClass('com://site/pages.data.object');
                $result = new $class($path);
            }

        }
        else
        {
            $namespace = parse_url($path, PHP_URL_SCHEME);

            if(!in_array($namespace, ['http', 'https'])) {
                $result = $this->getObject('data.registry')->fromPath($path);
            } else {
                $result = $this->getObject('data.registry')->fromUrl($path, $cache);
            }
        }

        return $result;
    }

    protected function _getConfig($identifier = 'com://site/pages.config')
    {
        if (is_string($identifier) && strpos($identifier, ':') === false) {
            $identifier = 'com://site/pages.'.$identifier;
        }

        return clone $this->getObject($identifier)->getConfig();
    }

    protected function _unboxObject($object)
    {
        if(is_object($object))
        {
            if(method_exists($object, 'toArray')) {
                $properties = $object->toArray();
            } elseif($object instanceof Traversable) {
                $properties = iterator_to_array($object);
            } else {
                $properties = get_object_vars($object);
            }
        }
        else $properties = $object;

        return $properties;
    }
}