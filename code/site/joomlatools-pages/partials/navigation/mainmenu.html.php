<? $pages = collection('pages', ['folder' => $folder ?? '.', 'level' => 3,  'recurse' => 'true', 'filter' => ['visible' => 'true']])  ?>
<? foreach ($pages as $page) :?>
<a class="px-2 md:pl-0 md:mr-3 md:pr-3 text-gray-700 no-underline md:border-r border-gray-light" href="<?= route($page); ?>"><?= $page->name; ?></a>
<? endforeach ?>