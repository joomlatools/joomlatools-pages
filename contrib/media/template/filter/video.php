<?php

class ExtMediaTemplateFilterVideo extends ComPagesTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_LOWEST,
            'enable'   => JDEBUG ? false : true,
        ));

        parent::_initialize($config);
    }

    public function enabled()
    {
        return (bool) $this->getConfig()->enable;
    }

    //See: https://github.com/aFarkas/lazysizes/blob/gh-pages/plugins/unveilhooks/ls.unveilhooks.js#L9
    public function filter(&$text)
    {
        //Do not filter the images if we are rendering the page
        if($this->getTemplate()->getLayout() !== NULL && $this->enabled())
        {
            $matches = array();
            if(preg_match_all('#<video\s([^>]*?[\'\"][^>]*?)>#iU', $text, $matches))
            {
                foreach($matches[1] as $key => $match)
                {
                    $attribs = $this->parseAttributes($match);
                    $poster  = $attribs['poster'] ?? null;
                    $valid   = !isset($attribs['data-src']) && (!isset($attribs['data-lazyload']) || $attribs['data-lazyload'] == 'true');

                    //Only handle none filtered videos
                    if($poster && $valid)
                    {
                        //Convert class to array
                        if(isset($attribs['class'])) {
                            $attribs['class'] = explode(' ', $attribs['class']);
                        }

                        //Preload
                        if(isset($attribs['preload'])) {
                            $attribs['data-preload'] = $attribs['preload'];
                        } else {
                            $attribs['data-preload'] = 'metadata';
                        }

                        $attribs['preload'] = 'none';

                        //Poster
                        if(isset($attribs['poster']))
                        {
                            $attribs['data-poster'] = $attribs['poster'];
                            unset($attribs['poster']);

                            //We have a poster image do not preload
                            $attribs['data-preload'] = 'none';
                        }

                        //Add lazyload
                        $attribs['class'][] = 'lazyload';


                        //Filter the images
                        $text = str_replace($matches[1][$key], $this->buildAttributes($attribs), $text);

                        //Import lazysizes
                        $text .= $this->getTemplate()->helper('ext:media.video.import', 'unveilhooks');
                    }
                }
            }
        }
    }
}
