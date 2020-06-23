<div class="flex items-center bg-blue-200 px-2 py-1">
	<a class="flex-initial w-1/5" href="/">
		<img class="object-contain h-24 w-full" src="<?= config()->site->logo ?>" alt="<?= config()->site_name ?> logo" />
	</a>
	<nav class="flex-1 w-auto inline-block text-right">
		<h2 id="mainmenu-label" class="hidden">Main Menu</h2>
		<?= import('/partials/navigation/mainmenu'); ?>
	</nav>
</div>