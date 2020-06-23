<ul class="list-none space-x-4">
    <? foreach (collection('pages') as $page) : ?>
        <li class="inline-block">
            <a href="<?= route($page); ?>"><?= $page->name; ?></a>
        </li>
    <? endforeach ?>
</ul>