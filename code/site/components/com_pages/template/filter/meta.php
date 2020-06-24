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
        if(!$included && !empty($text))
        {
            $meta = array();

            foreach($this->_getMetadata() as $name => $content)
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

            $meta[] = $this->_getCanonical();

            $text = implode("", $meta).$text;

            $included = true;
        }
    }

    protected function _getMetadata()
    {
        $metadata = array();
        if($page = $this->getTemplate()->page())
        {
            if($page->metadata)
            {
                if($page->isCollection() && $this->getTemplate()->state()->isUnique())
                {
                    if($metadata = $this->getTemplate()->collection()->metadata) {
                        $metadata->append($page->metadata);
                    } else {
                        $metadata = $page->metadata;
                    }
                }
                else $metadata = $page->metadata;

                $metadata = KObjectConfig::unbox($metadata);

                if(isset($metadata['og:type']))
                {
                    if (strpos($metadata['og:image'], 'http') === false) {
                        $metadata['og:image'] = (string)$this->getTemplate()->url($metadata['og:image']);
                    }

                    if (!$metadata['og:url']) {
                        $metadata['og:url'] = (string)$this->getTemplate()->route($page);
                    }

                    if (strpos($metadata['og:url'], 'http') === false) {
                        $metadata['og:url'] = (string)$this->getTemplate()->url($metadata['og:url']);
                    }
                }
            }
        }

        return $metadata;
    }

    protected function _getCanonical()
    {
        $canonical = '';
        if($page = $this->getTemplate()->page())
        {
            $canonical = $page->canonical ?: '';

            if($page->isCollection() && $this->getTemplate()->state()->isUnique()) {
                $canonical = $this->getTemplate()->collection()->get('canonical', $canonical);
            }

            if($canonical) {
                $canonical = sprintf('<link href="%s" rel="canonical" />', $canonical);
            }
        }

        return $canonical;
    }
}