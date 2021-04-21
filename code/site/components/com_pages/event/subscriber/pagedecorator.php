<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesEventSubscriberPagedecorator extends ComPagesEventSubscriberAbstract
{
    private $__dispatcher;
    private $__decorated;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function onAfterApplicationInitialise(KEventInterface $event)
    {
        if($this->isDecoratable())
        {
            $page     = $this->getDispatcher()->getPage();
            $decorate = $page->process->get('decorate', false);

            if(is_object($decorate))
            {
                $decorate->title = $page->name;
                $decorate->alias = $page->slug;
                $decorate->route = $this->getDispatcher()->getRoute()->getPath();

                ComPagesDecoratorMenu::getInstance()->addItem($decorate->toArray());
            }
        }
    }

    public function onAfterApplicationRoute(KEventInterface $event)
    {
        if($this->isDecoratable())
        {
            //Try to validate the cache
            $dispatcher = $this->getDispatcher();

            if($dispatcher->isCacheable()) {
                $dispatcher->validate();
            }
        }
    }

    public function onAfterApplicationDispatch(KEventInterface $event)
    {
        if($this->isDecoratable())
        {
            $dispatcher = $this->getDispatcher();

            //Set metadata in page
            $data = JFactory::getDocument()->getHeadData();
            $dispatcher->getPage()->title   = $data['title'];
            $dispatcher->getPage()->summary = $data['description'];

            ob_start();

            $dispatcher->getResponse()->setContent('<ktml:component>');
            $dispatcher->dispatch();

            ob_end_clean();

            $this->__decorated = true;
        }
    }

    public function onBeforeKoowaPageControllerRender(KEventInterface $event)
    {
        if($this->isDecoratable())
        {
            $dispatcher = $this->getDispatcher();

            if($dispatcher->getDecorator() != 'joomla')
            {
                $data    = JFactory::getDocument()->getHeadData();
                $version = JFactory::getDocument()->getMediaVersion();
                $options = JFactory::getDocument()->getScriptOptions();

                $result = array();

                // Generate link declarations
                foreach ($data['links'] as $link => $attributes)
                {
                    $link = '<link href="'.$link.'" '.$attributes['relType'].'="'.$attributes['relation'].'"';

                    if (is_array($attributes['attribs']))
                    {
                        if ($temp =  Joomla\Utilities\ArrayHelper::toString($attributes['attribs'])) {
                            $link .= ' ' . $temp;
                        }
                    }

                    $link .= ' />';

                    $result[] = $link;
                }


                // Generate stylesheet links
                foreach ($data['styleSheets'] as $src => $attribs)
                {
                    // Conditional statements
                    if(isset($attribs['options']) && isset($attribs['options']['conditional'])) {
                        $attribs['condition'] = $attribs['options']['conditional'];
                    }

                    // Version
                    if(isset($attribs['options']['version']) && $attribs['options']['version'])
                    {
                        if(strpos($src, '?') === false && ($version || $attribs['options']['version'] !== 'auto')) {
                            $src .= '?' . ($attribs['options']['version'] === 'auto' ? $version : $attribs['options']['version']);
                        }
                    }

                    unset($attribs['options']);
                    unset($attribs['type']);
                    unset($attribs['mime']);

                    $style = '<ktml:style src="'.$src.'"';

                    // Add script tag attributes.
                    foreach ($attribs as $attrib => $value)
                    {
                        $style .= ' ' . htmlspecialchars($attrib, ENT_COMPAT, 'UTF-8');

                        $value = !is_scalar($value) ? json_encode($value) : $value;
                        $style .= '="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"';
                    }

                    $style .= ' />';

                    $result[] = $style;
                }

                // Generate stylesheet declarations
                foreach ($data['style'] as $style) {
                    $result[]= '<style>'.$style.'</style>';
                }

                // Generate scripts option
                if (!empty($options))
                {
                    $script = '<script type="application/json" class="joomla-script-options new">';

                    $prettyPrint = (JDEBUG && defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : false);
                    $jsonOptions = json_encode($options, $prettyPrint);
                    $jsonOptions = $jsonOptions ? $jsonOptions : '{}';

                    $script .= $jsonOptions.'</script>';

                    $result[] = $script;
                }

                // Generate script file links
                foreach ($data['scripts'] as $src => $attribs)
                {
                    // Conditional statements
                    if(isset($attribs['options']) && isset($attribs['options']['conditional'])) {
                        $attribs['condition'] = $attribs['options']['conditional'];
                    }

                    // Version
                    if(isset($attribs['options']['version']) && $attribs['options']['version'])
                    {
                        if (strpos($src, '?') === false && ($version || $attribs['options']['version'] !== 'auto')) {
                            $src .= '?' . ($attribs['options']['version'] === 'auto' ? $version : $attribs['options']['version']);
                        }
                    }

                    unset($attribs['options']);
                    unset($attribs['type']);
                    unset($attribs['mime']);

                    $script = '<ktml:script src="'.$src.'"';

                    // Add script tag attributes.
                    foreach ($attribs as $attrib => $value)
                    {
                        // B/C: If defer and async is false or empty don't render the attribute.
                        if (in_array($attrib, array('defer', 'async')) && !$value) {
                            continue;
                        }

                        if (in_array($attrib, array('defer', 'async')) && $value === true) {
                            $value = $attrib;
                        }

                        $script .= ' ' . htmlspecialchars($attrib, ENT_COMPAT, 'UTF-8');

                        if (!in_array($attrib, array('defer', 'async')))
                        {
                            $value = !is_scalar($value) ? json_encode($value) : $value;
                            $script .= '="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"';
                        }
                    }

                    $script .= ' />';

                    $result[] = $script;
                }

                // Generate script declarations
                foreach ($data['script'] as $type => $content)
                {
                    $script = '<script';

                    $types  = array('text/javascript', 'application/javascript', 'text/x-javascript', 'application/x-javascript');
                    if (!is_null($type) && !in_array($type, $types)) {
                        $script .= ' type="'. $type.'" ';
                    }

                    $script .= '>'.$content.'</script>';

                    $result[] = $script;
                }

                // Output the custom tags - array_unique makes sure that we don't output the same tags twice
                foreach (array_unique($data['custom']) as $custom) {
                    $result[] = $custom;
                }

                $content =  $event->response->getContent();
                $content .= implode("\n", $result);

                $event->response->setContent($content);
            }
        }
    }

    public function onAfterKoowaPageControllerRender(KEventInterface $event)
    {
        if($this->isDecoratable())
        {
            $dispatcher = $this->getDispatcher();

            $buffer = JFactory::getDocument()->getBuffer('component');

            $content = $event->result;
            $content = str_replace('<ktml:component>', $buffer, $content);

            if($dispatcher->getDecorator() == 'joomla') {
                JFactory::getDocument()->setBuffer($content, 'component');
            } else {
                $event->result = $content;
            }
        }
    }

    public function getDispatcher()
    {
        if(is_null($this->__dispatcher))
        {
            if($route = $this->getObject('com://site/pages.dispatcher.http')->getRoute())
            {
                $this->__dispatcher = false;

                $page     = $this->getObject('com://site/pages.dispatcher.http')->getPage();
                $decorate = $page->process->get('decorate', false);

                if($decorate !== false)
                {
                    $this->__dispatcher = $this->getObject('com://site/pages.dispatcher.http')
                        ->setController('decorator');
                }
            }
        }

        return $this->__dispatcher;
    }

    public function isDecoratable()
    {
        $result = false;

        //Only decorate GET requests that are not routing to com_pages
        if($this->getObject('request')->isGet()) {
            $result = (bool) $this->getDispatcher();
        }

        return $result;
    }

    public function isDecorated()
    {
        return (bool) $this->__decorated;
    }
}

class ComPagesDecoratorMenu extends \Joomla\CMS\Menu\SiteMenu
{
    private static $__instance;

    public static function getInstance($client = 'site', $options = array())
    {
        if(!self::$__instance instanceof ComPagesDecoratorMenu) {
            self::$__instance = self::$instances[$client] = new ComPagesDecoratorMenu($options);
        }

        return self::$__instance;
    }

    public function addItem($attributes)
    {
        //Instantiate the site menu decorator
        $query = [
            'option' => 'com_'.$attributes['component']
        ];

        if(!empty($attributes['view'])) {
            $query['view'] = $attributes['view'];
        }

        if(!empty($attributes['layout'])) {
            $query['layout'] = $attributes['layout'];
        }

        if(!empty($attributes['layout'])) {
            $query['layout'] = $attributes['layout'];
        }

        if(!empty($attributes['id'])) {
            $query['id'] = $attributes['id'];
        }

        $item = new \Joomla\CMS\Menu\MenuItem();

        $item->id = max(array_keys($this->_items)) + 1;
        $item->route    = trim($attributes['route'], '/');
        //$item->menutype = $attributes['menu'] ?? null;
        $item->title = $attributes['title'] ?? null;
        $item->alias = $attributes['alias'] ?? null;
        $item->link = 'index.php?'.http_build_query($query);
        $item->type = 'component';
        $item->access = $attributes['access'] ?? 1;
        $item->level  = $attributes['level'] ?? 1;
        $item->language  = $attributes['language'] ?? '*';
        $item->parent_id = $attributes['parent'] ?? 1;
        $item->home = 0;
        $item->component = $query['option'];
        $item->component_id = JComponentHelper::getComponent($query['option'])->id;
        $item->query        = $query;

        //Set the params
        $item->setParams($attributes['params'] ?? array());

        $this->_items = [$item->id => $item] + $this->_items;

        foreach ($this->_items as &$item)
        {
            $parent_tree = array();
            if (isset($this->_items[$item->parent_id])) {
                $parent_tree  = $this->_items[$item->parent_id]->tree;
            }

            // Create tree.
            $parent_tree[] = $item->id;
            $item->tree = $parent_tree;
        }
    }
}