<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterMeta extends ComPagesTemplateFilterAbstract
{
    public function filter(&$text)
    {
        static $included;

        //Ensure we are only including the page metadata once and do not include metadata if the page is empty
        if($this->isEnabled() && !$included && !empty($text))
        {
            $meta = array();
            $metadata = $this->unbox($this->metadata());

            foreach($metadata as $name => $content)
            {
                if($content)
                {
                    $content =  is_array($content) ? implode(', ', $content) : $content;
                    $content =  htmlspecialchars($content, ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', false);

                    if(strpos($name, 'og:') === 0) {
                        $meta[] = sprintf('<meta property="%s" content="%s" />', $name,  $content);
                    } else {
                        $meta[]  = sprintf('<meta name="%s" content="%s" />', $name, $content);
                    }
                }
            }

            if($canonical = $this->canonical()) {
                $meta[] = sprintf('<link href="%s" rel="canonical" />', $canonical);
            }

            $text = implode("", $meta).$text;

            $included = true;
        }
    }
}