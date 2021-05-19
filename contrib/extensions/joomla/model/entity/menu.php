<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaModelEntityMenu extends ComPagesModelEntityItem
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'data' => [
                'id'		  => 0,
                'slug'        => '',
                'title'       => '',
                'type'        => '',
                'link'        => '',
                'default'     => false,
                'parameters'  => [],
                'language'    => 'en-GB',
            ],
        ]);

        parent::_initialize($config);
    }

    public function setPropertyParameters($value)
    {
        if($value && is_string($value)) {
            $value = json_decode($value, true);
        }

        //Filter out params that are NULL or empty string
        $value = array_filter($value, function($value) { return !is_null($value) && $value !== ''; });

        $config  = new ComPagesObjectConfig($value);
        $config->merge($value);

        return $config;
    }

    public function getPropertyQuery()
    {
        $query = array();

        $url = str_replace('index.php?', '', $this->link);
        $url = str_replace('&amp;', '&', $url);

        parse_str($url, $query);

        return  array_filter($query);
    }

    public function getPropertyVisible()
    {
        $result = true;

        if (($this->parameters->get('menu_show', 1) == 0)) {
            $result = false;
        }

        return $result;
    }

    public function getRoute()
    {
        $route = $this->link;

        switch ($this->type)
        {
            case 'separator': break;
            case 'heading'  : break;

            case 'url':
                if ((strpos($this->link, 'index.php?') === 0) && (strpos($this->link, 'Itemid=') === false)) {
                    $route = $this->link . '&Itemid=' . $this->id;
                }
                break;

            case 'alias':
                $route = 'index.php?Itemid=' . $this->parameters->get('aliasoptions');

                // Get the language of the target menu item when site is multilingual
                if (JLanguageMultilang::isEnabled())
                {
                    $item = JFactory::getApplication()->getMenu()->getItem((int) $this->parameters->get('aliasoptions'));

                    // Use language code if not set to ALL
                    if ($item != null && $item->language && $item->language !== '*') {
                        $route .= '&lang=' . $item->language;
                    }
                }

                break;

            default:
                $route = 'index.php?Itemid='.$this->id;
        }

        if ((strpos($route, 'index.php?') !== false) && strcasecmp(substr($route, 0, 4), 'http')) {
            $url = JRoute::_($route, true, $this->parameters->get('secure'));
        } else {
            $url = JRoute::_($route);
        }

        return $url;
    }
}