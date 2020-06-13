<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

interface ComPagesPageInterface
{
    public function getType();

    public function isRedirect();
    public function isForm();
    public function isCollection();
    public function isDecorator();

    public function isSubmittable();
    public function isEditable();
}