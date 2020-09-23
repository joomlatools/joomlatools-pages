<?php

class ExtMediaTemplateHelperVideo extends ComPagesTemplateHelperBehavior
{
    public function player($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'debug' =>  JFactory::getApplication()->getCfg('debug'),
            'quality_default' => '540',
            'quality_lowest'  => '240'
        ));

        $html = '';
        if (!static::isLoaded('plyr'))
        {
            $html .= '<ktml:script src="https://unpkg.com/plyr@3.6.2/dist/plyr.'.(!$config->debug ? 'min.js' : 'js').'" defer="defer" />';
            $html .= '<ktml:style src="https://unpkg.com/plyr@3.6.2/dist/plyr.css" defer="defer" />';

            $html .= <<<PLYR
<script>
document.addEventListener("lazyloaded", (e) =>
{
    if(e.target.tagName.toLowerCase() == 'video')
    {
        const video = e.target

       if ("connection" in navigator && navigator.connection.saveData === true) {
            var quality = {$config->quality_lowest}
        } else {
            var quality = {$config->quality_default}
        }

        if(!video.canPlayType('application/x-mpegURL')) {
            var settings = ['quality']
        } else {
            var settings = []
        }

        var player = new Plyr(video, {
            fullscreen: { enabled: true, fallback: true, iosNative: true, container: null },
            settings: settings,
            quality: { default: quality, options: [720, 540, 360, 240] }
        })
    }
})
</script>
PLYR;

            static::setLoaded('plyr');
        }

        return $html;
    }

    public function import($plugin = '', $config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'debug' =>  JFactory::getApplication()->getCfg('debug'),
        ));

        $html   = '';
        $script = $plugin ? 'lazysizes-'.$plugin : 'lazysizes';
        if (!static::isLoaded($script))
        {
            if($script == 'lazysizes') {
                $html .= '<ktml:script src="https://unpkg.com/lazysizes@5.2.2/lazysizes.'.(!$config->debug ? 'min.js' : 'js').'" defer="defer" />';
            }

            if($script == 'lazysizes-unveilhooks')
            {
                $html .= $this->import();
                $html .= '<ktml:script src="https://unpkg.com/lazysizes@5.2.2/plugins/unveilhooks/ls.unveilhooks.' . (!$config->debug ? 'min.js' : 'js') . '" defer="defer" />';
            }

            static::setLoaded($script);
        }

        return $html;
    }
}