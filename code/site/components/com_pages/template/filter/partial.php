<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateFilterPartial extends ComPagesTemplateFilterAbstract
{
    public function filter(&$text)
    {
        $types = $this->getObject('template.engine.factory')->getFileTypes();
        $types = implode('|', $types);

        $matches = array();
        if(preg_match_all('#<ktml:partial\s+format="('.$types.')">(.*)<\/ktml:partial>#siU', $text, $matches))
        {
            foreach($matches[0] as $key => $match)
            {
                $engine = $this->getObject('template.engine.factory')
                    ->createEngine($matches[1][$key], array('template' => $this->getTemplate()));

                $result = $engine->loadString(trim($matches[2][$key]))->render();

                $text = str_replace($match, $result, $text);

            }
        }
    }
}
