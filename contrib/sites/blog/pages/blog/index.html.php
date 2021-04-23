---
layout: default
name: Blog
title: Pages Joomla Blog
summary: Catch up on the latest news and updates.
slug: blog
collection:
    model: ext:joomla.model.articles
    state:
        limit: 0
        published: 1
        sort: date
        order: desc
        category: [blog]
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

		<? foreach(collection() as $article): ?>
		
		<article class="mb-12">
			<h2 class="mb-4">
				<a href="<?= route('blog/article', ['slug' => $article->slug]) ?>" class="text-black text-xl md:text-2xl no-underline hover:underline">
					<?= $article->title; ?>
				</a>
			</h2>
			<div class="mb-4">
				<img itemprop="image" class="featured-image rounded object-cover object-left-top w-full" src="<?= ($article->image->url) ? $article->image->url : 'https://loremflickr.com/700/350' ; ?>" alt="<?= $article->title; ?>">
			</div>
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

		<div class="inline-block relative w-auto">
			<?= helper('paginator.pagination') ?>
			<div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
				<svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
			</div>
		</div>

	</div>
	<!--/ articles -->

	<?= import('/partials/page/sidebar'); ?>

</div>