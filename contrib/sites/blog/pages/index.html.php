---
@layout: /sidebar

name: Home
title: Homepage
summary: Description for the homepage
---

<ktml:block extend="main-header">
<h1 class=" text-xl md:text-4xl pb-4"><?= $title ?></h1>
<p class="leading-loose text-gray-dark">
    <?= $summary ?>
</p>
</ktml:block>

<!-- articles -->
<div class="w-full md:pr-12 mb-12 prose">
    <?
    $articles = collection('ext:joomla.model.articles', [
        'limit'     => 3,
        'published' => 1,
        'sort'      => 'date',
        'order'     => 'desc',
        'category'  => ['uncategorised']
    ]); ?>

    <? foreach($articles as $article): ?>

		<article class="mb-12">
			<h2 class="mb-4">
				<a href="<?= $article->getRoute() ?>" class="text-black text-xl md:text-2xl no-underline hover:underline">
					<?= $article->title; ?>
				</a>
			</h2>

			<div class="mb-4 text-sm text-gray-700">
				by <a href="#" class="text-gray-700"><?= $article->getAuthor()->getName(); ?></a>
        on <?= date('d M, Y', $article->published_date); ?>
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
