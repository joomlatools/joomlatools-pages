<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesTemplateFilterAbstract extends KTemplateFilterAbstract
{
    use ComPagesTemplateTraitFunction;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'enabled' => true,
        ));

        parent::_initialize($config);
    }

    public function isEnabled()
    {
        return $this->getConfig()->enabled;
    }
}