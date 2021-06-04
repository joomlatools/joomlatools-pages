<? foreach (data('navigation/footer') as $item) : ?>
<a <? if($item->path) : ?>href="<?= route($item->path); ?>"<? else: ?>rel="nofollow" href="<?= $item->url ?>"<? endif ?> class="text-black no-underline hover:underline ml-4">
	<?= $item->title; ?>
</a>
<? endforeach ?>