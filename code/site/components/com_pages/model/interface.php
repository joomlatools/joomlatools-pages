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
    public function persist();

    public function fetchData($count = false);
    public function filterData($data);
    public function filterItem($item, KModelStateInterface $state);

    public function getType();
}