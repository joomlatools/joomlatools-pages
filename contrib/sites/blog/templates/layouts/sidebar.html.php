---
@layout: /default
---

<div class="container max-w-4xl mx-auto md:flex items-start py-8 px-12 md:px-0">

<ktml:content>

<!-- sidebar -->
<aside class="w-full md:w-64">

    <section class="rounded shadow overflow-hidden mb-6">

        <h3 class="text-sm bg-gray-100 text-gray-700 py-3 px-4 border-b">
            Categories
        </h3>

        <div class="p-4">
            <ul class="list-reset leading-normal">

                <?  $categories = collection('ext:joomla.model.categories', [
                    'limit'     => 0,
                    'published' => 1,
                    'order'     => 'desc',
                ]); ?>

                <? foreach($categories as $category): ?>
                    <li><a href="#" class="text-gray-darkest text-sm"><?= $category->title; ?></a></li>
                <? endforeach ;?>

            </ul>
        </div>
    </section>

    <section class="rounded shadow overflow-hidden mb-6">

        <h3 class="text-sm bg-gray-100 text-gray-700 py-3 px-4 border-b">
            Latest Blog Posts
        </h3>

        <div class="p-4">
            <ul class="list-reset leading-normal">

                <? $articles = collection('blog', ['limit' => 0]); ?>

                <? foreach($articles as $article): ?>
                    <li>
                        <a href="<?= route('blog/article', ['slug' => $article->slug]) ?>" class="text-gray-darkest text-sm">
                            <?= $article->title; ?>
                        </a>
                    </li>
                <? endforeach ;?>
            </ul>
        </div>
    </section>

</aside>
<!-- /sidebar -->

</div>