<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateHelperHighlight extends ComKoowaTemplateHelperBehavior
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'debug'    => $this->getObject('pages.config')->debug,
            'selector' => 'body',
            'style'    => 'base16/flat',
            'version'    => '11.5.1',
        ));

        parent::_initialize($config);
    }

    public function __invoke($config = array())
    {
        $config = new ComPagesObjectConfig($config);
        $config->append($this->getConfig());

        $html = '';
        if (!static::isLoaded('highlight'))
        {
            $style  = 'https://unpkg.com/@highlightjs/cdn-assets@'.$config->version.'/styles/'.$config->style.'.min.css';
            $script = 'https://unpkg.com/@highlightjs/cdn-assets@'.$config->version.'/highlight.' . (!$config->debug ? 'min.js' : 'js');

            $html .= <<<HIGHLIGHT
<script>
document.addEventListener('DOMContentLoaded', (event) => {

    const observer = new IntersectionObserver(entries => {
    
        function createButton(el, text) {
            // Create the copy button and append it to the codeblock.
            let button = Object.assign(document.createElement("button"), {
              innerHTML: "Copy",
              className: "hljs-copy-button",
            });
            
            button.dataset.copied = false;
            el.parentElement.classList.add("hljs-copy-wrapper");
            el.parentElement.appendChild(button);
        
            // Add a custom proprety to the code block so that the copy button can reference and match its background-color value.
            el.parentElement.style.setProperty("--hljs-theme-background", window.getComputedStyle(el).backgroundColor);
        
            if (navigator.clipboard) {
                button.onclick = function() {
                    navigator.clipboard
                        .writeText(text)
                        .then(function () {
                            button.innerHTML = "Copied!";
                            button.dataset.copied = true;
                    
                            let alert = Object.assign(document.createElement("div"), {
                                role: "status",
                                className: "hljs-copy-alert",
                                innerHTML: "Copied to clipboard",
                            });
                            el.parentElement.appendChild(alert);
                        
                            setTimeout(() => {
                                button.innerHTML = "Copy";
                                button.dataset.copied = false;
                                el.parentElement.removeChild(alert);
                                alert = null;
                            }, 2000);
                        })
                }
            }   
        };
        
        function highlightElement(el) {
            hljs.addPlugin({
                'after:highlightElement': ({ el, result, text }) => {
                    createButton(el, text);
                }
            });
                           
            hljs.highlightElement(el);
        }   
    
        entries.forEach(entry => {
            if (entry.intersectionRatio > 0)  {
                if(typeof hljs == 'undefined') {
                    var style = document.createElement('link');
                    style.rel = 'stylesheet'
                    style.href = '$style'
                    document.head.appendChild(style)

                    var script = document.createElement('script')
                    script.async = false
                    script.src   = '$script'
                    document.head.appendChild(script)
                     
                    script.addEventListener('load', () => {
                        highlightElement(entry.target);
                    })
                } else {
                    highlightElement(entry.target);
                }
               
		        observer.unobserve(entry.target);
            }
        });
    });
    
    document.querySelectorAll('$config->selector pre').forEach((el) => {
        el.classList.add('highlight-container');
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
.highlight-container {
   cursor: pointer;
   border-radius: 0.25rem;
}

.hljs-copy-wrapper {
  position: relative;
  overflow: hidden;
}
.hljs-copy-wrapper:hover .hljs-copy-button,
.hljs-copy-button:focus {
  transform: translateX(0);
}
.hljs-copy-button {
  position: absolute;
  transform: translateX(calc(100% + 1.125em));
  top: 1em;
  right: 1em;
  width: 2rem;
  height: 2rem;
  text-indent: -9999px; /* Hide the inner text */
  color: #fff;
  border-radius: 0.25rem;
  border: 1px solid #ffffff22;
  background-color: var(--hljs-theme-background);
  background-image: url('data:image/svg+xml;utf-8,<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6 5C5.73478 5 5.48043 5.10536 5.29289 5.29289C5.10536 5.48043 5 5.73478 5 6V20C5 20.2652 5.10536 20.5196 5.29289 20.7071C5.48043 20.8946 5.73478 21 6 21H18C18.2652 21 18.5196 20.8946 18.7071 20.7071C18.8946 20.5196 19 20.2652 19 20V6C19 5.73478 18.8946 5.48043 18.7071 5.29289C18.5196 5.10536 18.2652 5 18 5H16C15.4477 5 15 4.55228 15 4C15 3.44772 15.4477 3 16 3H18C18.7956 3 19.5587 3.31607 20.1213 3.87868C20.6839 4.44129 21 5.20435 21 6V20C21 20.7957 20.6839 21.5587 20.1213 22.1213C19.5587 22.6839 18.7957 23 18 23H6C5.20435 23 4.44129 22.6839 3.87868 22.1213C3.31607 21.5587 3 20.7957 3 20V6C3 5.20435 3.31607 4.44129 3.87868 3.87868C4.44129 3.31607 5.20435 3 6 3H8C8.55228 3 9 3.44772 9 4C9 4.55228 8.55228 5 8 5H6Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7 3C7 1.89543 7.89543 1 9 1H15C16.1046 1 17 1.89543 17 3V5C17 6.10457 16.1046 7 15 7H9C7.89543 7 7 6.10457 7 5V3ZM15 3H9V5H15V3Z" fill="white"/></svg>');
  background-repeat: no-repeat;
  background-position: center;
  transition: background-color 200ms ease, transform 200ms ease-out;
}
.hljs-copy-button:hover {
  border-color: #ffffff44;
}
.hljs-copy-button:active {
  border-color: #ffffff66;
}
.hljs-copy-button[data-copied="true"] {
  text-indent: 0px; /* Shows the inner text */
  width: auto;
  background-image: none;
}
@media (prefers-reduced-motion) {
  .hljs-copy-button {
    transition: none;
  }
}

/* visually-hidden */
.hljs-copy-alert {
  clip: rect(0 0 0 0);
  clip-path: inset(50%);
  height: 1px;
  overflow: hidden;
  position: absolute;
  white-space: nowrap;
  width: 1px;
}
</style>

HIGHLIGHT;
            static::setLoaded('highlight');
        }

        return $html;
    }
}