---
layout: examples/holy-grid
name: Spreadsheet
title: Spreadsheet Example
summary: Spreadsheet model example to get you going
slug: spreadsheet
collection:
    model: ext:model.spreadsheet?path=logs/examples/results.xlsx
    state:
        limit: 20
---
<ul>
	<? foreach(collection() as $result) :  ?>
		<li>
			<?= $result->{'Test Name'} ?>
		</li>
	<? endforeach; ?>
</ul>
<?= helper('paginator.pagination') ?>