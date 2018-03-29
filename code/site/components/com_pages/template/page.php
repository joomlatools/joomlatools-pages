<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesTemplatePage extends KTemplate
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
                'data'   => function($path, $format = '') {
                    return  $this->getObject('com:pages.data.factory')->createObject($path, $format);
                }
            ),
            'cache'           => false,
            'cache_namespace' => 'pages',
        ));

        parent::_initialize($config);
    }

    /**
     * Load a template by path
     *
     * @param   string  $url      The template url
     * @throws \InvalidArgumentException If the template could not be located
     * @return KTemplate
     */
    public function loadFile($url)
    {
        //Locate the template
        if (!$file = $this->getObject('template.locator.factory')->locate($url)) {
            throw new InvalidArgumentException(sprintf('The file "%s" cannot be located.', $url));
        }

        //Get the page content
        $content = file_get_contents($file);
        $type    = pathinfo($file, PATHINFO_EXTENSION);

        if (strpos($content, "---") !== false)
        {
            $config = array();
            if(preg_match('#^\s*---(.*|[\s\S]*)\s*---#siU', $content, $matches))
            {
                //Inject the frontmatter into the template parameters
                $parameters = $this->getObject('object.config.factory')->fromString('yaml', $matches[1], false);

                $this->setParameters($parameters);
            }
        }

        return $this->loadString(str_replace($matches[0], '', $content), $type != 'html' ? $type : null, $url);
    }

    /**
     * Set the template content from a string
     *
     * Overrides TemplateInterface:loadString() and allows to define the type of content. If a type is set
     * an engine for the type will be created. If no type is set we will assumed the content has already been
     * rendered.
     *
     * @param  string   $source  The template content
     * @param  integer  $type    The template type.
     * @return KTemplate
     */
    public function loadString($source, $type = null, $url = null)
    {
        if($type)
        {
            //Create the template engine
            $config = array(
                'template'  => $this,
                'functions' => $this->_functions
            );

            $this->_source = $this->getObject('template.engine.factory')
                ->createEngine($type, $config)
                ->loadString($source, $url);
        }
        else parent::loadString($source);

        return $this;
    }
}