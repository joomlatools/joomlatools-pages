<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

defined('KOOWA') or die; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <? foreach(collection() as $page): ?>
    <? if(!$page->metadata->has('robots') || !in_array('noindex', (array) KObjectConfig::unbox($page->metadata->robots))): ?>
    <url>
        <loc><?= route($page) ?></loc>
        <lastmod><?= $page->date->format(DateTime::ATOM) ?></lastmod>
    </url>
    <? endif; ?>
    <? endforeach ?>

</urlset>