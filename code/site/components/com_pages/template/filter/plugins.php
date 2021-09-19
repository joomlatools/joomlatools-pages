<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterPlugins extends ComPagesTemplateFilterAbstract
{
    public function filter(&$text)
    {
        $matches = array();
        if(preg_match_all('#<ktml:plugins>(.*)<\/ktml:plugins>#siU', $text, $matches))
        {
            $page = $this->getTemplate()->page();

            foreach($matches[0] as $key => $match)
            {
                $content = new stdClass;
                $content->text = $matches[1][$key];

                $params = (object) $page->getProperties();

                //Trigger onContentBeforeDisplay
                $results = array();
                $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                    'name'         => 'onContentBeforeDisplay',
                    'import_group' => 'content',
                    'attributes'   => array('com_pages.item', &$content, &$params)
                ));

                //Trigger onContentPrepare
                $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                    'name'         => 'onContentPrepare',
                    'import_group' => 'content',
                    'attributes'   => array('com_pages.item', &$content, &$params)
                ));

                //Trigger onContentAfterDisplay
                $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                    'name'         => 'onContentAfterDisplay',
                    'import_group' => 'content',
                    'attributes'   => array('com_pages.item', &$content, &$params)
                ));

                $result = trim(implode("\n", $results));

                $text = str_replace($match, $result, $text);
            }
        }
    }
}
