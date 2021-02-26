<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtMediaTemplateHelperVideo extends ExtMediaTemplateHelperLazysizes
{
    public function player($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'quality_default' => '540',
            'quality_lowest'  => '240',
            'selector'        => 'video'
        ));

        $html = '';
        if (!static::isLoaded('plyr'))
        {
            $script = 'https://unpkg.com/plyr@3.6.2/dist/plyr.'.(!$config->debug ? 'min.js' : 'js');
            $style  = 'https://unpkg.com/plyr@3.6.2/dist/plyr.css';

            $html .= $this->import('unveilhooks');
            $html .= <<<PLYR
<script>
document.addEventListener("lazybeforeunveil", (e) =>
{
    if((typeof Plyr == 'undefined') && e.target.matches('{$config->selector}'))
    {
        const video = e.target

        var style = document.createElement('link');
        style.rel = 'stylesheet'
        style.href = '{$style}'
        document.head.appendChild(style)

        var script = document.createElement('script')
        script.async = false
        script.src   = '{$script}'
        document.head.appendChild(script)

        script.addEventListener('load', () =>
        {
            if ('connection' in navigator && (navigator.connection.saveData === true || navigator.connection.effectiveType.includes('2g'))) {
                var quality = {$config->quality_lowest}
            } else {
                var quality = {$config->quality_default}
            }

            if(!video.canPlayType('application/x-mpegURL')) {
                var settings = ['quality']
            } else {
                var settings = []
            }

             document.querySelectorAll('{$config->selector}').forEach((p) => new Plyr(p, {
                fullscreen: { enabled: true, fallback: true, iosNative: true, container: null },
                settings: settings,
                quality: { default: quality, options: [720, 540, 360, 240] }
             }));
        });
    }
})
</script>
PLYR;

            static::setLoaded('plyr');
        }

        return $html;
    }
}