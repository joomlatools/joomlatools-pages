<ul class="list-none">
    <? foreach (collection('pages') as $page) : ?>
    <li>
        <a href="<?= route($page); ?>"><?= $page->name; ?></a>
    </li>
    <? endforeach ?>
</ul>