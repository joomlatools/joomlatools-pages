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
                'icon'      => "",
                'class'     => null,
                'truncate'  => null,
                'arialabel' => 'Anchor',
            ),
            'selector' => 'h2[id], h3[id], h4[id], h5[id], h6[id]',
        ));

        $html = '';
        if (!static::isLoaded('anchor'))
        {
            $html .= '<ktml:script src="assets://com_pages/js/'.($config->debug ? 'build/' : 'min/').'anchor.js" />';
            $html .= '<script>
            anchors.options = '.$config->options.'   
            // Add anchors on DOMContentLoaded
            document.addEventListener("DOMContentLoaded", function(event) {
                anchors.add('.json_encode($config->selector).');if(document.querySelector(\'.no-anchor\')!==null){anchors.remove(\'.no-anchor\');}
            }); </script>';

            static::setLoaded('anchor');
        }

        return $html;
    }
}