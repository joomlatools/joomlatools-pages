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
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">

    <channel>
        <title><?= translate('Pages') ?> - <?= escape($sitename)?></title>
        <description><![CDATA[<?= $description ?>]]></description>
        <link><?= url() ?></link>
        <? if (!empty($image)): ?>
            <image>
                <url><?=$image?></url>
                <title><?= translate('Pages') ?> - <?= escape($sitename)?></title>
                <link><?= route('format=html') ?></link>
            </image>
        <? endif; ?>
        <lastBuildDate><?= count(collection()) ? collection()->top()->date->format(DateTime::RSS) : '' ?></lastBuildDate>
        <atom:link href="<?=  url() ?>" rel="self" type="application/rss+xml"/>
        <language><?= $language ?></language>
        <sy:updatePeriod><?= $update_period ?></sy:updatePeriod>
        <sy:updateFrequency><?= $update_frequency ?></sy:updateFrequency>

        <?foreach(collection() as $page):?>
            <item>
                <title><?= escape($page->title); ?></title>
                <link><?= route($page); ?></link>
                <guid isPermaLink="true"><?= route($page); ?></guid>
                <description><![CDATA[
                 <? if($page->image): ?>
                    <img width="800" href="<?= $page->image ?>" />
                 <? endif ?>
                 <?= $page->content ?>
                ?>]]></description>
                <author><?= escape($page->author) ?></author>
                <category><?= escape($page->category) ?></category>
                <pubDate><?= $page->date->format(DateTime::RSS) ?></pubDate>
            </item>
        <?endforeach?>
    </channel>
</rss>