<? $pages = collection('pages', ['path' => $path ?? '/', 'level' => 1,  'recurse' => 'true'])  ?>
<? foreach ($pages as $page) :?>
    <a class="px-2 md:pl-0 md:mr-3 md:pr-3 text-gray-700 no-underline md:border-r border-gray-light" href="<?= route($page); ?>"><?= $page->name; ?></a>
<? endforeach ?>