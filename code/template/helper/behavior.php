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
}