<ktml:images max-width="80%" lazyload="progressive,inline">
<div class="max-w-4xl m-auto mt-8">
	<h1 role="heading" aria-level="1" itemprop="name" class="sm:text-5xl text-4xl font-medium font-title uppercase mb-2 text-gray-900 dark:text-gray-100 leading-none"><?= $article->title ?></h1>
	<div class="h-1 w-20 bg-brand rounded mb-6"></div>
	<div class="mb-6">
		<img itemprop="image" class="featured-image rounded object-cover object-left-top w-full" src="<?= $article->image->url ?>" alt="<?= $article->title; ?>">
	</div>
	<p class="mt-2 text-xs font-medium flex flex-row justify-between">
		<span itemprop="author" itemscope itemtype="http://schema.org/Person">
			<span itemprop="name"><?//= $article->getAuthor()->getName() ?></span>
		</span>
		<span class="leading-relaxed text-dark-green-500 flex items-center">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 mr-1" stroke="currentColor">
			  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
			</svg> <?= date($article->published_date, 'd M, Y'); ?>
		</span>
	</p>
	<div class="mt-2" itemprop="articleBody" content="<?= escape(strip_tags($article->excerpt.$article->text)) ?>"><?= $article->excerpt.$article->text ?></div>
</div>
</ktml:images>
