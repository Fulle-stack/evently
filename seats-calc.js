// 1. Находим поле "количество рядов" и сохраняем в переменную rows
let rows = document.getElementById('rows_count');

// 2. Находим поле "мест в ряду" и сохраняем в переменную seats
let seats = document.getElementById('seats_per_row');

// 3. Находим место, где показывать результат, и сохраняем в total
let total = document.getElementById('totalSeatsPreview');

// 4. Создаём функцию с названием "calc"
function calc() {
    // Берём значение из rows, превращаем в число. Если пусто → 0
    // Берём значение из seats, превращаем в число. Если пусто → 0
    // Умножаем
    // Показываем результат в total
    total.innerText = (parseInt(rows.value) || 0) * (parseInt(seats.value) || 0);
}

// 5. Когда пользователь вводит что-то в rows — запускаем calc
rows.oninput = calc;

// 6. Когда пользователь вводит что-то в seats — запускаем calc
seats.oninput = calc;