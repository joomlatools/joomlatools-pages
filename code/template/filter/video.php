<?php

class ComPagesTemplateFilterVideo extends ComPagesTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority'   => self::PRIORITY_LOW,
            'enabled'     => true,
            'parameters' => ['auto' => 'true'],
        ));

        parent::_initialize($config);
    }

    public function isEnabled()
    {
        return (bool) $this->getConfig()->enabled;
    }

    //See: https://github.com/aFarkas/lazysizes/blob/gh-pages/plugins/unveilhooks/ls.unveilhooks.js#L9
    public function filter(&$text)
    {
        //Filter the images only at the end of the rendering cycle
        if($this->isEnabled())
        {
            $matches = array();
            if(preg_match_all('#<video\s([^>]*?[\'\"][^>]*?)>#iU', $text, $matches))
            {
                foreach($matches[1] as $key => $match)
                {
                    $attribs = $this->parseAttributes($match);
                    $valid   = !isset($attribs['data-src']) && (!isset($attribs['data-lazyload']) || $attribs['data-lazyload'] == 'true');

                    //Only handle none filtered videos
                    if($valid)
                    {
                        //Convert class to array
                        if(isset($attribs['class'])) {
                            $attribs['class'] = explode(' ', $attribs['class']);
                        }

                        //Preload
                        if(!isset($attribs['preload']))
                        {
                            //Let browser preload
                            $attribs['data-preload'] = 'metadata';
                            $attribs['preload'] = 'metadata';
                        }
                        else $attribs['data-preload'] = $attribs['preload'];

                        //Poster
                        if(isset($attribs['poster']))
                        {
                            $attribs['data-poster'] = $this->poster($attribs['poster']);
                            unset($attribs['poster']);

                            //We have a poster image do not preload
                            $attribs['data-preload'] = 'none';
                            $attribs['preload'] = 'none';
                        }

                        //Add lazyload
                        $attribs['class'][] = 'lazyload';

                        //Filter the images
                        $text = str_replace($matches[1][$key], $this->buildAttributes($attribs), $text);

                        //Enable plyr (custom player)
                        $text .= $this->helper('video')->player();
                    }
                }
            }
        }
    }

    public function poster($url, $parameters = array())
    {
        $config = new ComPagesObjectConfig($parameters);
        $config->append($this->getConfig()->parameters);

        $url   = KHttpUrl::fromString($url);
        $query = array_merge(array_filter(KObjectConfig::unbox($config)), $url->query);

        ksort($query); //sort alphabetically
        $url->query = $query;

        return $url;
    }
}
