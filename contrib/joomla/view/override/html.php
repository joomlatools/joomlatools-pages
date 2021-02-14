<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaViewOverrideHtml extends KViewTemplate
{
    private $__delegate;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors'   => ['com://site/pages.view.behavior.layoutable'],
            'url'         =>  $this->getObject('request')->getUrl(),
            'template'    => 'com://site/pages.template.default',
            'auto_fetch'  => false,
            'template_filters' => ['version', 'asset'/*, 'meta'*/],
            'template_functions' => [
                'loadTemplate' => [$this, 'loadTemplate'],
                'loadHelper'   => [$this, 'loadHelper'],
                'direction'    => [$this, 'getDirection'],
                'language'     => [$this, 'getLanguage'],
            ],
        ]);

        parent::_initialize($config);
    }

    public function getDelegate()
    {
        return $this->__delegate;
    }

    public function setDelegate($delegate)
    {
        $this->__delegate = $delegate;

        $reflection = new ReflectionClass($delegate);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
        {
            $name = $method->name;
            if(!$this->getConfig()->template_functions->has($name)) {
                $this->getTemplate()->registerFunction($name, [$delegate, $name]);
            }
        }

        return $this;
    }

    public function loadHelper($hlp = null)
    {
        $result = '';

        if($delegate = $this->getDelegate()) {
            $result = $delegate->loadHelper($hlp);
        }

        return $result;
    }

    public function loadTemplate($tpl = null)
    {
        $result = '';

        if($delegate = $this->getDelegate())
        {
            if($override = $delegate->getOverride($tpl))
            {
                $result = $this->getTemplate()
                    ->loadFile($override)
                    ->render($delegate->getProperties());
            }
            else $result = $delegate->loadTemplate($tpl);
        }

        return $result;
    }

    public function getRoute($route = '', $fqr = false, $escape = true)
    {
        return parent::getRoute($route, $fqr, $escape);
    }

    protected function _actionRender(KViewContext $context)
    {
        $content = $this->getContent();
        return trim($content);
    }
}
