<? foreach (data('navigation/footer') as $item) : ?>
<a <? if($item->route) : ?>href="<?= route($item->route); ?>"<? else: ?>rel="nofollow" href="<?= $item->url ?>"<? endif ?> class="text-black no-underline hover:underline ml-4">
	<?= $item->title; ?>
</a>
<? endforeach ?>