<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/textman for the canonical source repository
 */
class ComPagesVersion extends KObject
{
    const VERSION = '0.1.0';

    /**
     * Get the version
     *
     * @return string
     */
    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * Return the version
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getVersion();
    }
}