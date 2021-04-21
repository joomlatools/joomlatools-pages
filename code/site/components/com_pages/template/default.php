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
            'filters'         => ['partial'],
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types' => ['html', 'txt', 'svg', 'css', 'js'],
        ]);

        //Register template functions (allows core functions to be overridden)
        $functions = array();
        foreach (glob(__DIR__.'/function/[!_]*.php') as $filename) {
            $functions[basename($filename, '.php')] = $filename;
        }

        $config->append(['functions' => $functions]);

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
            if($template->has('layout'))
            {
                if(is_string($template->layout)) {
                    $layout = ['path' => $template->layout];
                } else {
                    $layout = $template->layout;
                }

                $this->_layout = new ComPagesObjectConfig($layout);
            }
            else $this->_layout = false;

            //Store the data and remove the layout
            $this->_data = KObjectConfig::unbox($template->remove('layout'));

            //Store the type
            $this->_type = pathinfo($file, PATHINFO_EXTENSION);

            if(!in_array($this->_type, $this->_excluded_types))
            {
                unset($this->_functions['import']); //prevent conflict

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

    public function render(array $data = array())
    {
        unset($data['layout']);

        //$display_errors = ini_get('display_errors');
        //ini_set('display_errors', false);

        $result = parent::render($data);

        //Exception for html files
        if($this->_type == 'html') {
            $result = $this->filter();
        }

        //ini_set('display_errors', $display_errors);

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