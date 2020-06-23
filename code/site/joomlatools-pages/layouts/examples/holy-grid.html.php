---
layout: index
header:  []
content: []
---
<ktml:style src="theme://css/examples/holy-grid.css" rel="preload" as="style"/>
<div class="min-h-screen container mx-auto holy-grid">
	<header id="top" class="cleafix">
		<!-- Header content -->
		<?= import('/partials/structure/header'); ?>
	</header>

	<nav>
		<!-- Navigation -->
		<?= import('/partials/structure/sidebar1'); ?>
	</nav>

	<main class="px-2 py-1">
		<!-- Main content -->
		<ktml:content>
	</main>

	<aside>
		<!-- Sidebar / Ads -->
		<?= import('/partials/structure/sidebar2'); ?>
	</aside>

	<footer>
		<!-- Footer content -->
		<?= import('/partials/structure/footer'); ?>
	</footer>
</div>