
document.addEventListener('DOMContentLoaded', () => {
    // --- Логика календаря ---
    const monthNames = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];

    // Устанавливаем стартовую дату (Март 2026, как на макете)
    let currentDate = new Date(2026, 2);

    const monthDisplay = document.getElementById('month-display');
    const daysContainer = document.getElementById('calendar-days');
    const prevBtn = document.getElementById('prev-month');
    const nextBtn = document.getElementById('next-month');

    function renderCalendar() {
        daysContainer.innerHTML = '';
        monthDisplay.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;

        const firstDayIndex = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).getDay();
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
        const prevLastDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0).getDate();

        // Сдвигаем индексы, чтобы неделя начиналась с понедельника (а не с воскресенья)
        const startDay = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

        // Отрисовка дней предыдущего месяца
        for (let x = startDay; x > 0; x--) {
            const dayElement = document.createElement('span');
            dayElement.classList.add('day', 'prev-month');
            dayElement.textContent = prevLastDay - x + 1;
            daysContainer.appendChild(dayElement);
        }

        // Отрисовка дней текущего месяца
        for (let i = 1; i <= lastDay; i++) {
            const dayElement = document.createElement('span');
            dayElement.classList.add('day');
            dayElement.textContent = i;

            // Если это 5-е число (как на макете), делаем его активным по умолчанию
            if (i === 5 && currentDate.getMonth() === 9 && currentDate.getFullYear() === 2026) {
                dayElement.classList.add('active');
            }

            // Добавляем событие клика по дню
            dayElement.addEventListener('click', () => {
                // Убираем класс active у всех дней
                document.querySelectorAll('.calendar-days .day').forEach(d => d.classList.remove('active'));
                // Добавляем класс active кликнутому дню
                dayElement.classList.add('active');
            });

            daysContainer.appendChild(dayElement);
        }
    }

    // Переключение месяцев
    prevBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    // Первичная отрисовка
    if (daysContainer && monthDisplay) {
        renderCalendar();
    }
});