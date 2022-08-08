---
@route: /blog/[digit:page]?
@layout: /sidebar
@collection:
    model: ext:joomla.model.articles
    state:
        limit: 2
        published: 1
        sort: date
        order: desc
        category: [blog]

name: Blog
title: The blog
summary: Description for an very awesome blog
---

<link href="<?= route('/blog.rss') ?>" rel="alternate" type="application/rss+xml" title="<?= $title ?>"  />

<ktml:block extend="main-header">
<!-- title -->
<h1 class=" text-xl md:text-4xl pb-4">
    <?= $title ?>
</h1>

<p class="leading-loose text-gray-dark">
	<?= $summary ?>
</p>
<!-- /title -->
</ktml:block>

<!-- articles -->
<div class="w-full md:pr-12 mb-12 prose">
    <?= partial('/articles/list.html', ['articles' => collection()]); ?>
</div>
<!--/ articles -->
