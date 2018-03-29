<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

defined('KOOWA') or die; ?>

<div class="content" id="content" tabindex="-1">
    <div class="content__inner content__inner--spaced">
        <?= $page->render() ?>
    </div>
</div>
