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
    private $__template = null;
    private $__helpers  = array();
    private $__layout   = null;

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
            'filters'         => ['partial', 'block'],
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types'  => ['html', 'txt', 'svg', 'css', 'js'],
        ]);

        //Register template functions (allows core functions to be overridden)
        $functions = array();
        foreach (glob(__DIR__.'/function/[!_]*.php') as $filename) {
            $functions[basename($filename, '.php')] = $filename;
        }

        $config->append(['functions' => $functions]);

        parent::_initialize($config);
    }

    public function loadLayout($url)
    {
        //Locate the template
        if(!$file = $this->getObject('template.locator.factory')->locate($url))
        {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            throw new RuntimeException(sprintf('Cannot find %s: "%s"', $scheme, $url));
        }

        //Load the template
        $template = (new ComPagesObjectConfigFrontmatter())->fromFile($file);

        //Store the layout
        if($template->has('layout'))
        {
            if (is_string($template->layout)) {
                $layout = ['path' => $template->layout];
            } else {
                $layout = $template->layout;
            }

            $this->__layout = new ComPagesObjectConfig($layout);
        }
        else $this->__layout = false;

        //Store the template
        $this->__template = $template;

        return parent::loadFile($url);
    }

    public function loadPartial($url)
    {
        return parent::loadFile($url);
    }

    public function getLayout()
    {
        return $this->__layout;
    }

    public function getData()
    {
        //Only return properties (not attributes)
        return $this->__template->getProperties(false);
    }

    public function get($property, $default = null)
    {
        //Allow to access all template variables
        return $this->__template->get($property, $default);
    }

    public function render(array $data = array())
    {
        //$display_errors = ini_get('display_errors');
        //ini_set('display_errors', false);

        $result = parent::render($data);

        //Filter html files
        if($this->__template)
        {
            $type = pathinfo($this->__template->getFilename(), PATHINFO_EXTENSION);

            if($type == 'html') {
                $result = $this->filter();
            }
        }

        //ini_set('display_errors', $display_errors);

        return $result;
    }

    public function invokeHelper($identifier, ...$params)
    {
        //Get the function and helper based on the identifier
        if(strpos($identifier, ':') !== false)
        {
            if(substr_count($identifier, '.') == 1) {
                $identifier = $identifier.'.__invoke';
            }
        }
        else
        {
            if(strpos($identifier, '.') === false) {
                $identifier = $identifier.'.__invoke';
            }
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

    public function isFunction($name)
    {
        $result = false;

        if(isset($this->_functions[$name])) {
            $result = true;
        }

        return $result;
    }

    public function handleException(Exception &$exception)
    {
        if($exception instanceof KTemplateExceptionError)
        {
            $file   = file($exception->getFile());
            $buffer = file($exception->getPrevious()->getFile());

            //Estimate the location of the error in the source file
            $line = count($file) - count($buffer) + $exception->getLine() - 1;

            //Try to find the specific line in the source file
            foreach($file as $l => $text)
            {
                if($text == $buffer[$exception->getPrevious()->getLine()]) {
                    $line = $l;
                    break;
                }
            }

            $exception = new KTemplateExceptionError(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getSeverity(),
                $exception->getFile(),
                $line,
                $exception->getPrevious()
            );
        }
    }
}