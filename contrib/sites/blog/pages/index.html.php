---
layout: default
name: Home
title: Pages Joomla Content
summary: The easy to use page generator for Joomla
slug: home
visible: true
---

<!-- title -->
<div class="text-center px-6 py-12 mb-6 bg-gray-100 border-b">
	<h1 class=" text-xl md:text-4xl pb-4"><?= $title ?></h1>
	<p class="leading-loose text-gray-dark">
		<?= $summary ?>
	</p>
</div>
<!-- /title -->

<div class="container max-w-4xl mx-auto md:flex items-start py-8 px-12 md:px-0">
	<!-- articles -->
	<div class="w-full md:pr-12 mb-12 prose">
		<?
			$articles = collection('ext:joomla.model.articles', [
			'limit' => 6,
			'published' => 1,
			'sort' => 'date',
			'order' => 'desc',
		]); ?>

		<? foreach($articles as $article): ?>
		
		<article class="mb-12">
			<h2 class="mb-4">
				<a href="<?= route('blog/article', ['slug' => $article->slug]) ?>" class="text-black text-xl md:text-2xl no-underline hover:underline">
					<?= $article->title; ?>
				</a>
			</h2>

			<div class="mb-4 text-sm text-gray-700">
				by <a href="#" class="text-gray-700"><?= $article->getAuthor()->getName(); ?></a> on <?= date($article->published_date, 'd M, Y'); ?>
				<span class="font-bold mx-1"> | </span>
				<a href="#" class="text-gray-700"><?= $article->category->title; ?></a>
			</div>

			<p class="text-gray-700 leading-normal">
				<?= $article->excerpt; ?>
			</p>

		</article>
		
		<? endforeach ;?>

	</div>
	<!--/ articles -->
	
	<?= import('/partials/page/sidebar'); ?>

</div>