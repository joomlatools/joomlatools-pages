<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */
class ComPagesVersion extends KObject
{
    const VERSION = '0.19.3';

    public function getVersion()
    {
        return self::VERSION;
    }

    public function __toString()
    {
        return $this->getVersion();
    }
}