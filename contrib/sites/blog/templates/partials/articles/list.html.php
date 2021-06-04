<? foreach($articles as $article): ?>

    <article class="mb-12">
        <h2 class="mb-4">
            <a href="<?= route('/blog/article', ['slug' => $article->slug]) ?>" class="text-black text-xl md:text-2xl no-underline hover:underline">
                <?= $article->title; ?>
            </a>
        </h2>
        <div class="mb-4">
            <? $image = $article->image->url ?? 'https://picsum.photos/seed/'.$article->id.'/700/350' ;  ?>
            <img itemprop="image" class="featured-image rounded object-cover object-left-top w-full" src="<?= $image ?>" alt="<?= $article->title; ?>">
        </div>
        <div class="mb-4 text-sm text-gray-700">
            by <a href="#" class="text-gray-700"><?= $article->getAuthor()->getName(); ?></a> on <?= date('d M, Y', $article->published_date); ?>
            <span class="font-bold mx-1"> | </span>
            <a href="#" class="text-gray-700">
                <?= $article->category->title; ?>
            </a>
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