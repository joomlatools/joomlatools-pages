<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */
class ComPagesVersion extends KObject implements KObjectSingleton
{
    const VERSION = '0.21.1';

    public function getVersion()
    {
        return self::VERSION;
    }

    public function __toString()
    {
        return $this->getVersion();
    }
}