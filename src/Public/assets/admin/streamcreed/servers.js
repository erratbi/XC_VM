(function () {
	'use strict';

	var inventory = document.querySelector('[data-sc-server-inventory]');
	if (!inventory) return;

	var search = inventory.querySelector('[data-sc-server-search]');
	var status = inventory.querySelector('[data-sc-server-status-filter]');
	var cards = Array.prototype.slice.call(inventory.querySelectorAll('[data-sc-inventory-card]'));
	var empty = inventory.querySelector('[data-sc-filter-empty]');

	function applyFilters() {
		var query = search ? search.value.trim().toLowerCase() : '';
		var selectedStatus = status ? status.value : 'all';
		var visible = 0;

		cards.forEach(function (card) {
			var matchesSearch = !query || (card.getAttribute('data-search') || '').indexOf(query) !== -1;
			var matchesStatus = selectedStatus === 'all' || card.getAttribute('data-status') === selectedStatus;
			card.hidden = !(matchesSearch && matchesStatus);
			if (!card.hidden) visible += 1;
		});

		if (empty) empty.hidden = visible !== 0;
	}

	if (search) search.addEventListener('input', applyFilters);
	if (status) status.addEventListener('change', applyFilters);
}());
