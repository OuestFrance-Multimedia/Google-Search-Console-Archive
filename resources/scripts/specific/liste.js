$(function() {
	// Table Sort
	$('#list').tablesorter();

	// Click on Table Event
	$(document).on(
			'mousedown',
			'.clickable',
			function(event) {
				// Middle Click or CTRL Click
				if ((event.which == 2)
						|| ((event.which == 1) && (event.ctrlKey == true))) {
					window.open($(this).data('href'), 'window name');
				} else {
					// Normal Click
					window.document.location = $(this).data('href');
				}
				event.preventDefault();
				return false;
			});

	$('.export').on('click', function(event) {
		exportTableToCSV.apply(this, [ $('#list'), 'export.csv' ]);
	});

	$.fn.graphup.colorMaps.colorMap = [ [ 221, 68, 55 ], [ 232, 129, 120 ], [],
			[ 180, 209, 58 ], [ 132, 155, 36 ] ];
	$.fn.graphup.colorMaps.colorMapInverted = [ [ 132, 155, 36 ],
			[ 180, 209, 58 ], [], [ 232, 129, 120 ], [ 221, 68, 55 ] ];
	$('#list td._i').graphup({
		colorMap : 'colorMap',
		painter : 'fill',
		max : 20,
		min : -15
	});
	$('#list td._c').graphup({
		colorMap : 'colorMap',
		painter : 'fill',
		max : 3,
		min : -3
	});
	$('#list td._p').graphup({
		colorMap : 'colorMapInverted',
		painter : 'fill',
		max : 1,
		min : -1
	});

	// Overlay & Popup Handling
	function overlay() {
		element = document.getElementById('filter-overlay');
		element.style.visibility = (element.style.visibility == 'visible') ? 'hidden'
				: 'visible';
		element = document.getElementById('filter-popup');
		element.style.visibility = (element.style.visibility == 'visible') ? 'hidden'
				: 'visible';
	}
	$('#filter-overlay').on('click', function(event) {
		overlay();
		return false;
	});
	$('#filter-handling').on('click', function(event) {
		overlay();
		return false;
	});

	// Initializing
	$('#filters>option').each(function() {
		if ($('#filter-value').val() == this.value) {
			$('#filter-name').val(this.label);
		}
	});

	// Form Handling
	$('#filter-value').on('input change', function(event) {
		var current = this.value;
		$('#filters>option').each(function() {
			if (current == this.value) {
				$('#filter-name').val(this.label);
			}
		});

	});

	$('#search').on('input change', function(event) {
		current = this.value;
		var found = false;
		$('#filter-value').val(current);
		$('#filters>option').each(function() {
			if (current == this.value) {
				found = true;
				$('#filter-name').val(this.label);
			}
		});
		if (found == false) {
			$('#filter-name').val('');
		}
	});

	// Adding Filter
	$('#filter-add, #filter-delete').on('click', function(event) {
		var name = $('#filter-name').val();
		var value = $('#filter-value').val();
		var action = null;

		if (this.id == 'filter-add') {
			action = 'add'
		} else {
			action = 'delete'
		}

		if (name && value) {
			$.ajax({
				url : 'ajax.php',
				data : {
					'mode' : 'filter',
					'action' : action,
					'website' : $('.form>input[name=website]').val(),
					'query' : $('.form>input[name=query]').val(),
					'name' : name,
					'value' : value
				}
			}).done(function() {

			});
		}
	});

});