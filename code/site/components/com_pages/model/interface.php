<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

interface ComPagesModelInterface extends KModelInterface
{
    const PERSIST_SUCCESS  = 1;
    const PERSIST_NOCHANGE = 2;
    const PERSIST_FAILURE  = 3;

    public function persist();

    public function getType();
    public function getIdentityKey();
    public function getPrimaryKey();
    public function getHash();

    public function isAtomic();
}