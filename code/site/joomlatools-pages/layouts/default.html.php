---
layout: index
---
		<!-- header -->
		<header class="w-full px-6 bg-white">
			<div class="container mx-auto max-w-4xl md:flex justify-between items-center">
				<a href="#" class="block py-6 w-full text-center md:text-left md:w-auto text-gray-dark no-underline flex justify-center items-center">
					<?= config()->site->name ?>
				</a>
				<div class="w-full md:w-auto text-center md:text-right">
					<!-- Login / Register -->
				</div>
			</div>
		</header>
		<!-- /header -->

		<!-- nav -->
		<nav class="w-full bg-white md:pt-0 px-6 relative z-20 border-t border-b border-gray-light">
			<div class="container mx-auto max-w-4xl md:flex justify-between items-center text-sm md:text-md md:justify-start">
				<div class="w-full md:w-1/2 text-center md:text-left py-4 flex flex-wrap justify-center items-stretch md:justify-start md:items-start">
					<?= import('/partials/navigation/mainmenu',['levels'=>1,]); ?>
				</div>
				<div class="w-full md:w-1/2 text-center md:text-right">
					<!-- extra links -->
				</div>
			</div>
		</nav>
		<!-- /nav -->

		<!-- main-content -->
		<div class="w-full bg-white">

			<ktml:content>

		</div>
		<!-- /main-content -->


		<!-- footer -->
		<footer class="w-full bg-white px-6 border-t">
			<div class="container mx-auto max-w-4xl py-6 flex flex-wrap md:flex-no-wrap justify-between items-center text-sm">
				&copy;<?= date('now', 'Y'); ?> <?= config()->site->name ?>. All rights reserved.
				<div class="pt-4 md:p-0 text-center md:text-right text-xs">
					<?= import('/partials/navigation/footer'); ?>
				</div>
			</div>
		</footer>
		<!-- /footer -->