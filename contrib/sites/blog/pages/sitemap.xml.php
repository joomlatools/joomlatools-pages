---
@process:
    cache: false

metadata:
    robots: [none]
state:
    visible: false
---

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <? $pages = collection('pages',
        state: [
            'level'  => 0,
            'limit'  => 0,
            'sort'   => 'date',
            'order'  => 'desc',
        ],
        filter: [
            'metadata' => [
                'robots' => ['nin:noindex', 'nin:none', 'nin:nositemap'],
            ],
            'redirect' => 'null',
        ]
    ); ?>

    <? $urls = []; ?>
    <? foreach($pages as $page): ?>

        <? $url = (string) route($page); ?>

        <? if($url) : ?>
            <url>
                <loc><?= $url ?></loc>
                <lastmod><?= $page->date->format(DateTime::ATOM); ?></lastmod>
            </url>

            <? $urls[$url] = $url; ?>
        <? endif ?>
    <? endforeach ?>

    <? $cache = collection('/api/cache.json',
        state: ['limit' => 0],
        filter: [
            'robots' => ['nin:noindex', 'nin:none', 'nin:nositemap'],
            'format' => 'html',
            'status' => 200,
        ]
    ); ?>

    <? foreach($cache as $item) : ?>

        <? if(!isset($urls[$item->url])) : ?>
            <url>
                <loc><?= escape($item->url) ?></loc>
                <lastmod><?= $item->date->format(DateTime::ATOM); ?></lastmod>
            </url>
        <? endif ?>
    <? endforeach ?>
</urlset>
