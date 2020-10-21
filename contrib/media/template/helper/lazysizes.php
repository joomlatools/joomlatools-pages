<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtMediaTemplateHelperLazysizes extends ComPagesTemplateHelperBehavior
{
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
            if($script == 'lazysizes')
            {
                $html .= '<ktml:script src="https://unpkg.com/lazysizes@5.2.2/lazysizes.'.(!$config->debug ? 'min.js' : 'js').'" defer="defer" />';
                $html .= <<<LAZYSIZES
<script>
window.lazySizesConfig = window.lazySizesConfig || {};

const lazySizesCache = 'lazySizesCache:'+location.pathname
if(sessionStorage.getItem(lazySizesCache) && performance.navigation.type != PerformanceNavigation.TYPE_RELOAD){
  window.lazySizesCache = JSON.parse(sessionStorage.getItem(lazySizesCache))
} else {
  window.lazySizesCache  = []
}

window.addEventListener('beforeunload', (event) => {
    sessionStorage.setItem(lazySizesCache, JSON.stringify(window.lazySizesCache))
})

if ('connection' in navigator)
{
    //Only load nearby elements
    if(navigator.connection.effectiveType.includes('2g')) {
        window.lazySizesConfig.loadMode = 2
    }

    //Only load visible elements
    if(navigator.connection.saveData === true) {
        window.lazySizesConfig.loadMode = 1
    }
}

document.addEventListener("DOMContentLoaded", () =>
{
    document.querySelectorAll('img[data-srclow]').forEach((img) =>
    {
        img.src = img.getAttribute('data-srclow')
         if(window.lazySizesCache.includes(img.dataset.hash)) {
            img.classList.remove('progressive')
         }
    })
})

document.addEventListener("lazybeforeunveil", (e) =>
{
    if(hash = e.target.dataset.hash)
    {
        if(!window.lazySizesCache.includes(hash)) {
            window.lazySizesCache.push(hash)
        }
    }
})
</script>

<style>
img.progressive {
    filter: blur(8px);
    transition: filter 400ms;
}

img.progressive.lazyloaded {
    filter: blur(0);
}
</style> 
LAZYSIZES;
            }

            if($script == 'lazysizes-bgset')
            {
                $html .= $this->import();
                $html .= '<ktml:script src="https://unpkg.com/lazysizes@5.2.2/plugins/bgset/ls.bgset.' . (!$config->debug ? 'min.js' : 'js') . '" defer="defer" />';
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
