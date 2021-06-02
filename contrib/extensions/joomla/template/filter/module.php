<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Module Template Filter
 *
 * This filter allow to dynamically inject data into module position.
 *
 * Filter will parse elements of the form <ktml:module position="[position]">[content]</ktml:module> and prepend or
 * append the content to the module position.
 *
 * Filter will parse elements of the form <html:module position="[position]" name="[module]"> create the module on
 * the fly and prepend or append the content to the module position.
 *
 * Filter will parse elements of the form <html:modules position="[position]" condition=[condition]>[content]</ktml:modules>
 * and inject the content into the template.
 *
 * The modules will not be rendered if there are no position defined, an optional condition attribute can be defined to
 * define a more advanced condition as to when the placeholder should be rendered. Only if the condition evaluates to
 * TRUE the modules will be rendered.
 *
 * Example <khtml:modules position="sidebar" condition="sidebar >= 2"> In this case the sidebar will be rendered only
 * if at least two modules have been injected.
 *
 * Params can be passed along when creating a module and should be encoded as a json string and especaded for xml
 * compliance.
 *
 * Example <ktml:module position="sidebar" name="menu" params="<?= json(['menutype' => 'mainmenu'], true); ?>">
 */
class ExtJoomlaTemplateFilterModule extends ComPagesTemplateFilterAbstract
{
    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config Configuration options
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_LOW,
        ));

        parent::_initialize($config);
    }

    /**
     * Find tags
     *
     * @param string $text Block of text to parse
     * @return ExtJoomlaTemplateFilterModule
     */
    public function filter(&$text)
    {
        $this->_parseModuleTags($text);
        $this->_parseModulesTags($text);

        return $this;
    }

    /**
     * Parse <ktml:module position="..."></ktml:module> and <ktml:module name="..."> tags
     *
     * @param string $text Block of text to parse
     */
    protected function _parseModuleTags(&$text)
    {
        $matches = array();

        if(preg_match_all('#<ktml:module\s+([^>]*)>(.*)</ktml:module>#siU', $text, $matches))
        {
            foreach($matches[0] as $key => $match)
            {
                //Create attributes array
                $attributes = array(
                    'style'     => 'component',
                    'params'    => '',
                    'title'     => '',
                    'class'     => '',
                    'prepend'   => true,
                );

                $attributes = array_merge($attributes, $this->parseAttributes($matches[1][$key]));
                $content    = trim($matches[2][$key]);

                //Skip empty modules
                if (!empty($content)) {
                    $this->_injectModule('koowa_injector', $attributes, $content);
                }
            }

            //Remove the <khtml:module></khtml:module> tags
            $text = str_replace($matches[0], '', $text);
        }

        if(preg_match_all('#<ktml:module\s+([^>]*)>#siU', $text, $matches))
        {
            foreach($matches[0] as $key => $match)
            {
                //Create attributes array
                $attributes = array(
                    'style'     => 'component',
                    'params'    => '',
                    'title'     => '',
                    'class'     => '',
                    'prepend'   => true,
                );

                $attributes = array_merge($attributes, $this->parseAttributes($matches[1][$key]));

                //Skip unexisting modules
                if(isset($attributes['name']) && ExtJoomlaTemplateFilterModuleHelper::hasModule($attributes['name'])) {
                    $this->_injectModule($attributes['name'], $attributes);
                }
            }

            //Remove the <khtml:module> tags
            $text = str_replace($matches[0], '', $text);
        }
    }

    /**
     * Parse <ktml:modules position="..."> and <ktml:modules position="..."></ktml:modules> tags
     *
     * @param string $text Block of text to parse
     */
    protected function _parseModulesTags(&$text)
    {
        $replace = array();
        $matches = array();

        // <ktml:modules position="[position]"></ktml:modules>
        if(preg_match_all('#<ktml:modules\s+position="([^"]+)"(.*"[^"]*")?>(.*)</ktml:modules>#siU', $text, $matches))
        {
            $count = count($matches[1]);

            for($i = 0; $i < $count; $i++)
            {
                $position = $matches[1][$i];
                $attribs  = $this->parseAttributes( $matches[2][$i] );
                $modules  = &ExtJoomlaTemplateFilterModuleHelper::getModules($position);

                if(isset($attribs['condition']))
                {
                    if($this->_countModules($attribs['condition']))
                    {
                        unset($attribs['condition']);
                        $replace[$i] = $this->_renderModules($modules, $attribs);
                    }
                }
                else  $replace[$i] = $this->_renderModules($modules, $attribs);

                if(!empty($replace[$i])) {
                    $replace[$i] = str_replace('<ktml:modules:content>', $replace[$i], $matches[3][$i]);
                }
            }

            $text = str_replace($matches[0], $replace, $text);
        }

        // <ktml:modules position="[position]">
        if(preg_match_all('#<ktml:modules\s+position="([^"]+)"(.*"[^"]*")?>#iU', $text, $matches))
        {
            $count = count($matches[1]);

            for($i = 0; $i < $count; $i++)
            {
                $position = $matches[1][$i];
                $attribs  = $this->parseAttributes( $matches[2][$i] );
                $modules  = &ExtJoomlaTemplateFilterModuleHelper::getModules($position);

                if(isset($attribs['condition']))
                {
                    if($this->_countModules($attribs['condition']))
                    {
                        unset($attribs['condition']);
                        $replace[$i] = $this->_renderModules($modules, $attribs);
                    }
                }
                else  $replace[$i] = $this->_renderModules($modules, $attribs);
            }

            $text = str_replace($matches[0], $replace, $text);
        }
    }

    /**
     * Render the modules
     *
     * @param object $modules   The module object
     * @param array  $attribs   Module attributes
     * @return string  The rendered modules
     */
    public function _renderModules($modules, $attribs = array())
    {
        $html  = '';
        $count = 1;
        foreach($modules as $module)
        {
            //Set the chrome styles
            if(isset($attribs['style'])) {
                $module->chrome  = explode(' ', $attribs['style']);
            }

            //Set the module attributes
            if($count == 1) {
                $attribs['rel']['first'] = 'first';
            }

            if($count == count($modules)) {
                $attribs['rel']['last'] = 'last';
            }

            if(!isset($module->attribs)) {
                $module->attribs = $attribs;
            } else {
                $module->attribs = array_merge($module->attribs, $attribs);
            }

            //Render the module
            $content = ExtJoomlaTemplateFilterModuleHelper::renderModule($module, $attribs);

            //Prepend or append the module
            if(isset($module->attribs['prepend']) && $module->attribs['prepend']) {
                $html = $content.$html;
            } else {
                $html = $html.$content;
            }

            $count++;
        }

        return $html;
    }

    /**
     * Count the modules based on a condition
     *
     * @param  string $condition
     * @return integer Returns the result of the evaluated condition
     */
    protected function _countModules($condition)
    {
        $operators = '(\+|\-|\*|\/|==|\!=|\<\>|\<|\>|\<=|\>=|and|or|xor)';
        $words = preg_split('# ' . $operators . ' #', $condition, null, PREG_SPLIT_DELIM_CAPTURE);

        for ($i = 0, $n = count($words); $i < $n; $i += 2)
        {
            // Odd parts (blocks)
            $name = strtolower($words[$i]);
            if(!is_numeric($name)) {
                $words[$i] = count(ExtJoomlaTemplateFilterModuleHelper::getModules($name));
            } else {
                $words[$i] = $name;
            }
        }

        //Use the stream buffer to evaluate the condition
        $str = '<?php return ' . implode(' ', $words) .';';
        $buffer = $this->getObject('filesystem.stream.factory')->createStream('koowa-buffer://temp', 'w+b');
        $buffer->truncate(0);
        $buffer->write($str);
        $result = include $buffer->getPath();

        return $result;
    }

    /**
     * Inject the module
     *
     * @param  string $name
     * @param  array  $attributes
     * @param  string $content
     */
    protected function _injectModule($name, $attributes = array(), $content = null)
    {
        //Create module object
        $module            = new stdClass();
        $module->content   = $content;
        $module->id        = uniqid();
        $module->position  = $attributes['position'];
        $module->params    = htmlspecialchars_decode($attributes['params']);
        $module->showtitle = !empty($attributes['title']);
        $module->title     = $attributes['title'];
        $module->attribs   = $attributes;
        $module->user      = 0;
        $module->name      = $name;
        $module->module    = 'mod_'.$name;

        $modules = &ExtJoomlaTemplateFilterModuleHelper::getModules(null);

        if($module->attribs['prepend']) {
            array_unshift($modules, $module);
        } else {
            array_push($modules, $module);
        }
    }
}

class ExtJoomlaTemplateFilterModuleHelper extends JModuleHelper
{
    public static function &getModules($position)
    {
        if($position) {
            $modules =& JModuleHelper::getModules($position);
        } else {
            $modules =& JModuleHelper::_load();
        }

        return $modules;
    }

    public static function hasModule($name)
    {
        $path = JModuleHelper::getLayoutPath('mod_'.strtolower($name));
        return file_exists($path);
    }
}