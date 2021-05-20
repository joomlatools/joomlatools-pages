<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateEngineKoowa extends KTemplateEngineKoowa
{
    protected function _import($url, array $data = array())
    {
        //Qualify relative template url
        if (!parse_url($url, PHP_URL_SCHEME))
        {
            if (!$base = end($this->_stack)) {
                throw new \RuntimeException('Cannot qualify partial template url');
            }

            if(parse_url($base, PHP_URL_SCHEME) != 'template') {
                $parent = $base;
            } else {
                $parent = null;
            }

            $url = $this->getObject('template.locator.factory')
                ->createLocator('template')
                ->qualify('template:'.trim($url, '/'), $parent);

            if(array_search($url, $this->_stack))
            {
                throw new \RuntimeException(sprintf(
                    'Template recursion detected while importing "%s" in "%s"', $url, $base
                ));
            }
        }

        return parent::_import($url, $data);
    }
}