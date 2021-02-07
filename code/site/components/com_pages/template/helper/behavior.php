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
            'debug' =>  JFactory::getApplication()->getCfg('debug'),
            'options'  => array(
                'placement' => 'right',
                'visibale'  => 'hover',
                'icon'      => "",
                'class'     => null,
                'truncate'  => null,
                'arialabel' => 'Anchor',
            ),
            'selector' => 'article h2, article h3, article h4, article h5, article h6',
        ));

        $html = '';
        if (!static::isLoaded('anchor'))
        {
            $selector = json_encode($config->selector);

            $html .= '<ktml:script src="assets://com_pages/js/anchor-v4.2.1.'.(!$config->debug ? 'min.js' : 'js').'" defer="defer" />';
            $html .= <<<ANCHOR
<script>
document.addEventListener("DOMContentLoaded", () => {
     anchors.options = $config->options  
     anchors.add($selector);if(document.querySelector('.no-anchor')!==null){anchors.remove('.no-anchor');}
})
</script>
ANCHOR;

            static::setLoaded('anchor');
        }

        return $html;
    }

    public function prefetcher($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'debug'    =>  JFactory::getApplication()->getCfg('debug'),
            'selector' => 'header',
            'onload'   => $this->getObject('dispatcher')->isCacheable(),
            'onhover'  => $this->getObject('dispatcher')->isCacheable(),
        ));

        $html = '';
        if (!static::isLoaded('prefetcher'))
        {
            $html .= '<ktml:script src="assets://com_pages/js/prefetcher-v1.1.1.'.(!$config->debug ? 'min.js' : 'js').'" defer="defer" />';
            $html .= <<<PREFETCHER
<script>
document.addEventListener("DOMContentLoaded", () => {
    new Prefetcher($config)
})
</script>
PREFETCHER;

            static::setLoaded('prefetcher');
        }

        return $html;
    }
}