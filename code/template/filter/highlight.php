<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterHighlight extends ComPagesTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'debug' => $this->getObject('pages.config')->debug,
        ));

        parent::_initialize($config);
    }

    public function filter(&$text)
    {
        if ($this->isEnabled() && preg_match_all('#<pre>\s*<code\s*([^>]*)>(.*)<\/code>\s*</pre>#siU', $text, $matches))
        {
            $html = $this->helper('highlight', $this->getConfig());
            $text = $text."\n".$html;
        }
    }
}