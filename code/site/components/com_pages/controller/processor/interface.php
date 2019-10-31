<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

interface ComPagesControllerProcessorInterface
{
    public function processData(array $data);

    public function setChannel($name);
    public function getChannel();

    public function setRequest(KControllerRequestInterface $request);
    public function getRequest();
}