<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterVersion extends  ComPagesTemplateFilterAbstract
{
    public function filter(&$text)
    {
        $pattern = '~
            <ktml:(?:script|style)    # match ktml:script and ktml:style tags
            [^(?:src=)]+              # anything before src=
                src="                 # match the link
              ((?:theme://)           # starts with theme:// or media://
              [^"]+)"                 # match the rest of the link
             (.*)/>
        ~siUx';

        // Hold a list of already processed URLs
        $processed = array();

        if(preg_match_all($pattern, $text, $matches, PREG_SET_ORDER))
        {
            foreach ($matches as $match)
            {
                $url = $match[1];

                if (!in_array($url, $processed))
                {
                    $processed[] = $url;

                    $scheme = parse_url($url, PHP_URL_SCHEME);
                    $file   = $this->getObject('config')->getSitePath($scheme).'/'.str_replace($scheme.'://', '', $url);

                    if(file_exists($file))
                    {
                        $version = hash_file('crc32b', $file);
                        $suffix  = strpos($url, '?') === false ? '?version='.$version : '&version='.$version;
                        $text    = str_replace($url, $url.$suffix, $text);
                    }
                }
            }
        }
    }
}
