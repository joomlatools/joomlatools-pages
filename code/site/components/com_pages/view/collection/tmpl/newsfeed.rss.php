<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

defined('KOOWA') or die; ?>

<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:media="http://search.yahoo.com/mrss/">

    <channel>
        <title><?= page()->title ?></title>
        <description><?= page()->summary ?></description>
        <link><?= route(page()->path.'/'.page()->slug) ?></link>
        <? if (page()->metadata->has('og:image')): ?>
            <image>
                <url><?= url(page()->metadata->get('og:image')) ?></url>
                <title><?= page()->title ?></title>
                <link><?= route(page()->path.'/'.page()->slug) ?></link>
            </image>
        <? endif; ?>
        <lastBuildDate><?= count(collection()) ? collection()->top()->date->format(DateTime::RSS) : '' ?></lastBuildDate>
        <atom:link href="<?=  url() ?>" rel="self" type="application/rss+xml"/>
        <language><?= language() ?></language>
        <sy:updatePeriod><?= $update_period ?></sy:updatePeriod>
        <sy:updateFrequency><?= $update_frequency ?></sy:updateFrequency>

        <?foreach(collection() as $page):?>
            <item>
                <title><?= escape($page->title); ?></title>
                <link><?= route($page); ?></link>
                <guid isPermaLink="true"><?= route($page); ?></guid>
                <? if($page->image): ?>
                    <media:content medium="image" url="<?= url($page->image) ?>" />
                <? endif ?>
                <description><?=  escape($page->summary) ?></description>
                <? if($page->category): ?>
                    <category><?= escape($page->category) ?></category>
                <? endif; ?>
                <pubDate><?= $page->date->format(DateTime::RSS) ?></pubDate>
            </item>
        <?endforeach?>
    </channel>
</rss>