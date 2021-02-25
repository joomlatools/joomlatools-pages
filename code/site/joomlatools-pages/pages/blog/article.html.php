---
layout: default
route: blog/[:slug]
collection:
    extend: blog
metadata:
    'og:type': article
visible: false
---

<article itemscope itemtype="http://schema.org/Article" class="container max-w-4xl mx-auto py-8 px-12 md:px-0 prose">
	<span class="hidden" itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
		<span itemprop="name"><?= config()->site->name ?></span>
	</span>
	<?= import('/partials/articles/single.html', [
		'article' => collection(),
	]); ?>
</article>