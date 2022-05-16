<? $pages = collection('pages', ['folder' => $folder ?? '.', 'level' => 1,  'recurse' => 'true', 'filter' => ['visible' => 'neq:false']])  ?>
<? foreach ($pages as $page) :?>
    <a class="px-2 md:pl-0 md:mr-3 md:pr-3 text-gray-700 no-underline md:border-r border-gray-light" href="<?= route($page); ?>"><?= $page->name; ?></a>
<? endforeach ?>