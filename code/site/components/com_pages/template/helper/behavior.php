<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateHelperBehavior extends ComKoowaTemplateHelperBehavior
{
    public function anchor($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'debug' => JFactory::getApplication()->getCfg('debug'),
            'options'  => array(
                'placement' => 'right',
                'visibale'  => 'hover',
                'icon'      => '&#128279;',
                'class'     => null,
                'truncate'  => null,
                'arialabel' => 'Anchor',
            )
        ));

        $html = '';
        if (!static::isLoaded('anchor'))
        {
            $html .= '<ktml:script src="assets://com_pages/js/'.($config->debug ? 'build/' : 'min/').'anchor.js" />';
            $html .= '<script>
            // Add anchors on DOMContentLoaded
            document.addEventListener("DOMContentLoaded", function(event) {
                anchors.add();
            }); </script>';

            static::setLoaded('anchor');
        }

        return $html;
    }
}