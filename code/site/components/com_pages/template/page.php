<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplatePage extends ComPagesTemplateAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'functions' => [
                'route' => [$this, 'createRoute'],
            ],
        ));

        parent::_initialize($config);
    }

    public function createRoute($path)
    {
        $route = '';
        if(is_string($path)) {
            $route = 'route://path=' . $path;
        }

        if(is_array($path)) {
            $route = http_build_query($path, '', '&');
        }

        return $route;
    }
}