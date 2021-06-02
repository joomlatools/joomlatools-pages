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
    protected function _compile($source)
    {
        if($source)
        {
            $file = (new ComPagesObjectConfigFrontmatter())->fromString($source);
            $data = $file->getProperties(false);

            $source = '';

            if($data)
            {
                $data = var_export($data, true);
                $source = '<?php extract('.$data.',EXTR_SKIP) ?>'."\n";
            }

            $source .= $file->getContent();
        }

        return parent::_compile($source);
    }

    protected function _import($url, array $data = array())
    {
        if (!parse_url($url, PHP_URL_SCHEME))
        {
            if($base = end($this->_stack))
            {
                if(parse_url($base, PHP_URL_SCHEME) !== 'template') {
                    $url = 'template:'.trim($url, '/');
                }
            }
        }

        return parent::_import($url, $data);
    }
}