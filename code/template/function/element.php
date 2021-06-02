<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($tag, $attributes = [], $children = '')
{
    static $self_closing_tags = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    $attribs = $this->buildAttributes($attributes);
    $attribs = $attribs ? ' '.$attribs : '';
    $tag     = strtolower($tag);

    if (in_array($tag, $self_closing_tags)) {
        $element =  "<$tag$attribs>";
    } else if (strpos($tag, 'ktml:') === 0 && !$children) {
        $element = "<$tag$attribs />";
    }
    else
    {
        if (!is_scalar($children) && is_callable($children)) {
            $children = $children($tag, $attributes);
        }

        if (is_array($children)) {
            $children = implode("\n", $children);
        }

        $element = "<$tag$attribs>$children</$tag>";
    }

    return $element;
};