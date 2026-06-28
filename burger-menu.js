// Получаем элементы бургера и меню
const burger = document.querySelector('.burger');
// Получаем элемент меню внутри функции, чтобы он был доступен при каждом клике
const menu = document.querySelector('.menu');

// Добавляем обработчик клика на бургер
burger.addEventListener('click', () => {
    // Переключаем классы 'active' для бургера и меню
    burger.classList.toggle('active');
    menu.classList.toggle('active');
});
