---
route: blog/[digit:page]?
layout: sidebar
name: Blog
title: The blog
summary: Description for an very awesome blog
collection:
    model: ext:joomla.model.articles
    state:
        limit: 2
        published: 1
        sort: date
        order: desc
        category: [blog]
---

<link href="<?= route('blog.rss') ?>" rel="alternate" type="application/rss+xml" title="<?= $title ?>"  />

<ktml:module position="main-header">
<!-- title -->
<h1 class=" text-xl md:text-4xl pb-4">
    <?= $title ?>
</h1>

<p class="leading-loose text-gray-dark">
	<?= $summary ?>
</p>
<!-- /title -->
</ktml:module>

<!-- articles -->
<div class="w-full md:pr-12 mb-12 prose">

    <?= import('/partials/articles/list.html', [
         'articles' => collection(),
    ]); ?>

</div>
<!--/ articles -->
