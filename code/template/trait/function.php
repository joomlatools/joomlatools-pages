<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

trait ComPagesTemplateTraitFunction
{
    public function __call($method, $arguments)
    {
        if($this->getTemplate()->isFunction($method)) {
            $result = $this->getTemplate()->__call($method, $arguments);
        } else {
            $result = parent::__call($method, $arguments);
        }

        return $result;
    }
}
