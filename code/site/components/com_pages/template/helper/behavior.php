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
    public function prefetcher($config = array())
    {
        $config = new ComPagesObjectConfig($config);
        $config->append(array(
            'debug'    =>  $this->getObject('pages.config')->debug,
            'selector' => 'a.prefetch',
            'onload'   => true,
            'onhover'  => true,
            'version'  => $this->getObject('com://site/pages.version')->getVersion()
        ));

        $html = '';
        if (!static::isLoaded('prefetcher'))
        {
            $html .= '<ktml:script src="https://files.joomlatools.com/pages@'.$config->version.'/prefetcher.'.(!$config->debug ? 'min.js' : 'js').'" defer="defer" />';
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

    public function alpine($config = [])
    {
        $config = new KObjectConfigJson($config);
        $config->append([
            'debug'   => $this->getObject('pages.config')->debug,
            'version' => '2.8.2'
        ]);

        $html = '';

        if (!static::isLoaded('alpine'))
        {
            $html .= '<ktml:script src="https://unpkg.com/alpinejs@'.$config->version.'/dist/alpine.js" defer="defer" />';
            static::setLoaded('alpine');
        }

        return $html;
    }

    public function highlight($config = array())
    {
        $config = new ComPagesObjectConfig($config);
        $config->append(array(
            'debug'    => $this->getObject('pages.config')->debug,
            'selector' => 'body',
            'style'    => 'atom-one-light',
            'badge_icon' => true,
            'badge_lang' => true,
            'version'    => '10.7.1',
        ));

        if($config->badge_icon) {
            $config->badge_icon = 'initial';
        } else {
            $config->badge_icon = 'none';
        }

        if($config->badge_lang) {
            $config->badge_lang = 'initial';
        } else {
            $config->badge_lang = 'none';
        }

        $html = '';
        if (!static::isLoaded('highlight'))
        {
            $style_url = 'https://unpkg.com/@highlightjs/cdn-assets@'.$config->version.'/styles/'.$config->style.'.min.css';
            $hljs_url  = 'https://unpkg.com/@highlightjs/cdn-assets@'.$config->version.'/highlight.' . (!$config->debug ? 'min.js' : 'js');
            $badge_url = 'https://unpkg.com/highlightjs-badge@0.1.9/highlightjs-badge.' . (!$config->debug ? 'min.js' : 'js');
    
            $html .= '<ktml:style src= />';
            $html .= <<<HIGHLIGHT
<script>
document.addEventListener('DOMContentLoaded', (event) => {

    // Add HighlightJS-badge (options are optional)
    var options = {
        contentSelector: '$config->selector pre > code',
    };
    
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.intersectionRatio > 0)  {
                if(typeof hljs == 'undefined') {
                    var style = document.createElement('link');
                    style.rel = 'stylesheet'
                    style.href = '$style_url'
                    document.head.appendChild(style)

                    var script = document.createElement('script')
                    script.async = false
                    script.src   = '$hljs_url'
                    document.head.appendChild(script)
                    
                    var badge = document.createElement('script')
                    badge.async = false
                    badge.src   = '$badge_url'
                    document.head.appendChild(badge)
                    
                    script.addEventListener('load', () => {
                        hljs.highlightElement(entry.target);
                    })
                
                    badge.addEventListener('load', () => {
                        window.highlightJsBadge(options);  
                    })
                } else {
                    hljs.highlightElement(entry.target);
                    window.highlightJsBadge(options);
                }
               
		        observer.unobserve(entry.target);
            }
        });
    });
    
    document.querySelectorAll('$config->selector pre > code').forEach((el) => {
        observer.observe(el);
        
        el.addEventListener("dblclick", function() {
            var e = getSelection(),
            t = document.createRange();
            t.selectNodeContents(this), e.removeAllRanges(), e.addRange(t)
        }, false);
    });
});
</script>

<style>
pre .code-badge {
    cursor: pointer;
    padding: 3px 7px;
    background: #ccc;
}
pre .code-badge-language { display: $config->badge_lang;}
pre .code-badge-copy-icon {
    display: $config->badge_icon;
    background: url('data:image/svg+xml;base64,PHN2ZyBhcmlhLWhpZGRlbj0idHJ1ZSIgZm9jdXNhYmxlPSJmYWxzZSIgZGF0YS1wcmVmaXg9ImZhciIgZGF0YS1pY29uPSJjb3B5IiBjbGFzcz0ic3ZnLWlubGluZS0tZmEgZmEtY29weSBmYS13LTE0IiByb2xlPSJpbWciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDQ0OCA1MTIiPjxwYXRoIGZpbGw9ImN1cnJlbnRDb2xvciIgZD0iTTQzMy45NDEgNjUuOTQxbC01MS44ODItNTEuODgyQTQ4IDQ4IDAgMCAwIDM0OC4xMTggMEgxNzZjLTI2LjUxIDAtNDggMjEuNDktNDggNDh2NDhINDhjLTI2LjUxIDAtNDggMjEuNDktNDggNDh2MzIwYzAgMjYuNTEgMjEuNDkgNDggNDggNDhoMjI0YzI2LjUxIDAgNDgtMjEuNDkgNDgtNDh2LTQ4aDgwYzI2LjUxIDAgNDgtMjEuNDkgNDgtNDhWOTkuODgyYTQ4IDQ4IDAgMCAwLTE0LjA1OS0zMy45NDF6TTI2NiA0NjRINTRhNiA2IDAgMCAxLTYtNlYxNTBhNiA2IDAgMCAxIDYtNmg3NHYyMjRjMCAyNi41MSAyMS40OSA0OCA0OCA0OGg5NnY0MmE2IDYgMCAwIDEtNiA2em0xMjgtOTZIMTgyYTYgNiAwIDAgMS02LTZWNTRhNiA2IDAgMCAxIDYtNmgxMDZ2ODhjMCAxMy4yNTUgMTAuNzQ1IDI0IDI0IDI0aDg4djIwMmE2IDYgMCAwIDEtNiA2em02LTI1NmgtNjRWNDhoOS42MzJjMS41OTEgMCAzLjExNy42MzIgNC4yNDMgMS43NTdsNDguMzY4IDQ4LjM2OGE2IDYgMCAwIDEgMS43NTcgNC4yNDNWMTEyeiI+PC9wYXRoPjwvc3ZnPg==');
    background-size: 100% 100%;
}
pre .code-badge-check-icon {
    display: $config->badge_icon;
    background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGFyaWEtaGlkZGVuPSJ0cnVlIiBmb2N1c2FibGU9ImZhbHNlIiBkYXRhLXByZWZpeD0iZmFzIiBkYXRhLWljb249ImNoZWNrIiBjbGFzcz0ic3ZnLWlubGluZS0tZmEgZmEtY2hlY2sgZmEtdy0xNiIgcm9sZT0iaW1nIiB2aWV3Qm94PSIwIDAgNTEyIDUxMiIgc3R5bGU9IiYjMTA7ICAgIGNvbG9yOiAjMmFmZjMyOyYjMTA7Ij48cGF0aCBmaWxsPSJjdXJyZW50Q29sb3IiIGQ9Ik0xNzMuODk4IDQzOS40MDRsLTE2Ni40LTE2Ni40Yy05Ljk5Ny05Ljk5Ny05Ljk5Ny0yNi4yMDYgMC0zNi4yMDRsMzYuMjAzLTM2LjIwNGM5Ljk5Ny05Ljk5OCAyNi4yMDctOS45OTggMzYuMjA0IDBMMTkyIDMxMi42OSA0MzIuMDk1IDcyLjU5NmM5Ljk5Ny05Ljk5NyAyNi4yMDctOS45OTcgMzYuMjA0IDBsMzYuMjAzIDM2LjIwNGM5Ljk5NyA5Ljk5NyA5Ljk5NyAyNi4yMDYgMCAzNi4yMDRsLTI5NC40IDI5NC40MDFjLTkuOTk4IDkuOTk3LTI2LjIwNyA5Ljk5Ny0zNi4yMDQtLjAwMXoiLz48L3N2Zz4=');
    background-size: 100% 100%;
}
</style>

HIGHLIGHT;
            static::setLoaded('highlight');
        }

        return $html;
    }
}