(function () {
    'use strict';
    const input = document.getElementById('manualSearch');
    const clear = document.getElementById('manualClear');
    const chips = Array.from(document.querySelectorAll('[data-manual-filter]'));
    const items = Array.from(document.querySelectorAll('[data-manual-item]'));
    const empty = document.getElementById('manualNoResults');
    let filter = 'all';

    function update() {
        const query = (input ? input.value : '').trim().toLowerCase();
        let shown = 0;
        items.forEach(function (item) {
            const text = (item.textContent + ' ' + (item.dataset.manualKeywords || '')).toLowerCase();
            const matchesText = !query || text.indexOf(query) !== -1;
            const matchesFilter = filter === 'all' || item.dataset.manualCategory === filter;
            const visible = matchesText && matchesFilter;
            item.classList.toggle('manual-hidden', !visible);
            if (visible) shown++;
        });
        if (empty) empty.classList.toggle('show', shown === 0);
        if (clear) clear.classList.toggle('d-none', !query);
    }
    if (input) input.addEventListener('input', update);
    if (clear) clear.addEventListener('click', function () { input.value = ''; input.focus(); update(); });
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            filter = chip.dataset.manualFilter;
            chips.forEach(function (c) { c.classList.toggle('active', c === chip); });
            update();
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === '/' && document.activeElement !== input) { event.preventDefault(); if (input) input.focus(); }
        if (event.key === 'Escape' && input) { input.value = ''; update(); input.blur(); }
    });
    update();
})();
