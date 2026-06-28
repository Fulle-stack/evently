// ============================================================================
//  Интерактивный конструктор зала (страница создания события)
//  - сетка ряды × места строится по числовым полям;
//  - кисти: «Проход» (убрать место) и до 3 ценовых зон;
//  - клик или протягивание раскрашивает места;
//  - точная раскладка кладётся в скрытое поле seats_layout как JSON.
// ============================================================================
(function () {
    'use strict';

    var hall       = document.getElementById('builderHall');
    var rowsInput  = document.getElementById('rows_count');
    var perInput   = document.getElementById('seats_per_row');
    var layoutEl   = document.getElementById('seatsLayout');
    var toolbar    = document.getElementById('builderToolbar');
    if (!hall || !rowsInput || !perInput || !layoutEl) return; // не та страница

    var MAX_ROWS = 26, MAX_PER = 50;

    // grid[r][c] = номер зоны (0..2) либо -1 (проход / нет места)
    var grid = [];
    var activeBrush = 'erase';   // 'erase' | 0 | 1 | 2
    var painting = false;

    // Цена зоны: Зона 1 берёт основную цену события, зоны 2-3 — свои поля
    function zonePrice(zone) {
        var el = zone === 0 ? document.getElementById('price')
               : document.getElementById('zonePrice' + zone);
        return el ? (parseInt(el.value, 10) || 0) : 0;
    }

    function rowLetter(r) { return String.fromCharCode(65 + r); }

    // --- (Пере)строить сетку из числовых полей -------------------------------
    function buildGrid() {
        var rows = Math.max(0, Math.min(MAX_ROWS, parseInt(rowsInput.value, 10) || 0));
        var per  = Math.max(0, Math.min(MAX_PER,  parseInt(perInput.value, 10) || 0));
        grid = [];
        for (var r = 0; r < rows; r++) {
            grid[r] = [];
            for (var c = 0; c < per; c++) grid[r][c] = 0; // по умолчанию — Зона 1
        }
        render();
    }

    // --- Отрисовать зал ------------------------------------------------------
    function render() {
        hall.innerHTML = '';
        if (grid.length === 0 || grid[0].length === 0) {
            var empty = document.createElement('div');
            empty.className = 'builder-empty';
            empty.textContent = 'Зал без мест — продажа по количеству билетов.';
            hall.appendChild(empty);
            updateOutput();
            return;
        }
        for (var r = 0; r < grid.length; r++) {
            var rowEl = document.createElement('div');
            rowEl.className = 'builder-row';

            var lbl = document.createElement('span');
            lbl.className = 'builder-rowlabel';
            lbl.textContent = rowLetter(r);
            rowEl.appendChild(lbl);

            for (var c = 0; c < grid[r].length; c++) {
                var cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'bseat';
                cell.dataset.r = r;
                cell.dataset.c = c;
                cell.textContent = c + 1;
                applyCellClass(cell, grid[r][c]);
                rowEl.appendChild(cell);
            }
            hall.appendChild(rowEl);
        }
        updateOutput();
    }

    function applyCellClass(cell, zone) {
        cell.classList.remove('is-aisle', 'zone-c0', 'zone-c1', 'zone-c2');
        if (zone === -1) {
            cell.classList.add('is-aisle');
            cell.setAttribute('aria-label', 'Проход');
        } else {
            cell.classList.add('zone-c' + zone);
            cell.setAttribute('aria-label', 'Ряд ' + rowLetter(+cell.dataset.r) +
                ', место ' + (+cell.dataset.c + 1) + ', зона ' + (zone + 1));
        }
    }

    // --- Применить активную кисть к клетке -----------------------------------
    function paintCell(cell) {
        var r = +cell.dataset.r, c = +cell.dataset.c;
        if (!grid[r]) return;
        grid[r][c] = (activeBrush === 'erase') ? -1 : activeBrush;
        applyCellClass(cell, grid[r][c]);
        updateOutput();
    }

    // --- Собрать раскладку и счётчики ----------------------------------------
    function updateOutput() {
        var layout = [], counts = [0, 0, 0], total = 0;
        for (var r = 0; r < grid.length; r++) {
            for (var c = 0; c < grid[r].length; c++) {
                var z = grid[r][c];
                if (z === -1) continue;
                layout.push({ row: rowLetter(r), num: c + 1, price: zonePrice(z) });
                counts[z]++; total++;
            }
        }
        layoutEl.value = JSON.stringify(layout);
        setText('totalSeatsPreview', total);
        for (var i = 0; i < 3; i++) setText('zoneCount' + i, counts[i]);
    }

    function setText(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }

    // --- Выбор кисти ---------------------------------------------------------
    function setBrush(val, btn) {
        activeBrush = val;
        toolbar.querySelectorAll('.brush-btn').forEach(function (b) {
            var on = b === btn;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    if (toolbar) {
        toolbar.querySelectorAll('.brush-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                if (e.target.classList.contains('zone-price')) return; // клик по полю цены
                var b = btn.dataset.brush;
                setBrush(b === 'erase' ? 'erase' : parseInt(b, 10), btn);
            });
        });
        // изменение цены зоны — пересобрать раскладку (цены меняются)
        toolbar.querySelectorAll('.zone-price').forEach(function (inp) {
            inp.addEventListener('input', updateOutput);
            inp.addEventListener('click', function (e) { e.stopPropagation(); });
        });
    }
    var basePrice = document.getElementById('price');
    if (basePrice) basePrice.addEventListener('input', updateOutput);

    // --- Рисование кликом и протягиванием ------------------------------------
    hall.addEventListener('mousedown', function (e) {
        var cell = e.target.closest('.bseat');
        if (!cell) return;
        e.preventDefault();
        painting = true;
        paintCell(cell);
    });
    hall.addEventListener('mouseover', function (e) {
        if (!painting) return;
        var cell = e.target.closest('.bseat');
        if (cell) paintCell(cell);
    });
    document.addEventListener('mouseup', function () { painting = false; });

    // Тач: рисуем по перемещению пальца
    hall.addEventListener('touchmove', function (e) {
        var t = e.touches[0];
        var el = document.elementFromPoint(t.clientX, t.clientY);
        if (el && el.classList.contains('bseat')) { paintCell(el); e.preventDefault(); }
    }, { passive: false });
    hall.addEventListener('touchstart', function (e) {
        var cell = e.target.closest('.bseat');
        if (cell) { paintCell(cell); }
    });

    // --- Перестроение при изменении размеров ---------------------------------
    rowsInput.addEventListener('input', buildGrid);
    perInput.addEventListener('input', buildGrid);

    // Старт
    buildGrid();
})();
