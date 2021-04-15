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
    protected $__dispatcher;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    public function onAfterApplicationRoute(KEventInterface $event)
    {
        //Try to validate the cache
        if($dispatcher = $this->getDispatcher())
        {
            if($dispatcher->isCacheable()) {
                $dispatcher->validate();
            }
        }
    }

    public function onAfterApplicationDispatch(KEventInterface $event)
    {
        if($this->isDecorated())
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
        }
    }

    public function onBeforeKoowaPageControllerRender(KEventInterface $event)
    {
        if($this->isDecorated())
        {
            $dispatcher = $this->getDispatcher();

            if($dispatcher->getDecorator() != 'joomla')
            {
                $data    = JFactory::getDocument()->getHeadData();
                $version = JFactory::getDocument()->getMediaVersion();
                $options =  JFactory::getDocument()->getScriptOptions();

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
        if($this->isDecorated())
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
        $menu = JFactory::getApplication()->getMenu()->getActive();

        $component  = $menu ? $menu->component : '';
        $menu_route = $menu ? $menu->route : '';

        //Only decorate GET requests that are not routing to com_pages
        if(is_null($this->__dispatcher) && $this->getObject('request')->isGet() && $component != 'com_pages')
        {
            $page_route = $route = $this->getObject('com://site/pages.dispatcher.http')->getRoute();

            if($page_route)
            {
                $this->__dispatcher = false;
                $page_route = $page_route->getPath(false);

                $base  = trim(dirname($menu_route), '.');
                $route = trim(str_replace($base, '', $page_route), '/');

                $page = $base ? $base.'/'.$route : $route;

                $level = 0;
                while($page && !$this->getObject('page.registry')->isPage($page))
                {
                    if($route = trim(dirname($route), '.'))
                    {
                        $page = $base ? $base.'/'.$route : $route;
                        $level++;
                    }
                    else $page = false;
                }

                if($page !== false)
                {
                    $page     = $this->getObject('page.registry')->getPageEntity($page);
                    $decorate = $page->process->get('decorate', false);

                    if($decorate === true || (is_int($decorate) && ($decorate >= $level)))
                    {
                        $dispatcher = $this->getObject('com://site/pages.dispatcher.http')->setController('decorator');
                        $dispatcher->getPage()->setProperties($page);

                        $dispatcher->getResponse()->getHeaders()->set('Content-Location',  clone $dispatcher->getRequest()->getUrl());
                        $dispatcher->getRequest()->getUrl()->setPath('/'.$page->path);

                        $this->__dispatcher = $dispatcher;
                    }
                }
            }
        }

        return $this->__dispatcher;
    }

    public function isDecorated()
    {
        return (bool) $this->getDispatcher();
    }
}