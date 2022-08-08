<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateHelperPrefetcher extends ComKoowaTemplateHelperBehavior
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'options' => [
                'debug'    =>  $this->getObject('pages.config')->debug,
                'selector' => 'a.prefetch',
                'onload'   => true,
                'onhover'  => true,
            ],
            'version'  => $this->getObject('com:pages.version')->getVersion()
        ));

        parent::_initialize($config);
    }

    public function __invoke($config = array())
    {
        $config = new ComPagesObjectConfig($config);
        $config->append($this->getConfig());

        $html = '';
        if (!static::isLoaded('prefetcher'))
        {
            $html .= '<ktml:script src="https://files.joomlatools.com/pages@'.$config->version.'/resources/prefetcher/prefetcher.'.(!$config->options->debug ? 'min.js' : 'js').'" defer="defer" />';
            $html .= <<<PREFETCHER
<script>
document.addEventListener("DOMContentLoaded", () => {
    new Prefetcher($config->options)
})
</script>
PREFETCHER;

            static::setLoaded('prefetcher');
        }

        return $html;
    }
}