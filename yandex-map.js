// Параметры:
//   address - полный адрес (улица, город, страна)
//   venueName - название места проведения
//   street - улица (для подписи)
//   city - город (для подписи)
// Функция, которая создаёт карту и ставит метку
function initYandexMap(address, venueName, street, city) {

    // 1. СОЗДАЁМ КАРТУ
    var myMap = new ymaps.Map("yandex-map", {
        center: [55.76, 37.64],
        zoom: 12,
        // Отключаем все элементы управления, кроме зума и полноэкранного режима 'fullscreenControl'
        controls: ['zoomControl']
    });

    // 2. ГЕОКОДИРОВАНИЕ АДРЕСА
    ymaps.geocode(address).then(function (result) {
        // Если результат найден, ставим метку на карту
        // Проверяем, что результат не пустой
        if (result.geoObjects.getLength() > 0) {
            var coordinates = result.geoObjects.get(0).geometry.getCoordinates();
            myMap.setCenter(coordinates, 16);

            // Создаём метку с балуном, который показывает название места и адрес
            var placemark = new ymaps.Placemark(coordinates, {
                // Балун с названием места и адресом
                balloonContent: "<b>" + venueName + "</b><br>" + street + "<br>" + city,
                iconContent: ""
            }, {
                preset: 'islands#redIcon'
            });
            // Добавляем метку на карту
            myMap.geoObjects.add(placemark);
        }
    });

    // 3. ФУНКЦИЯ ИСПРАВЛЕНИЯ РАЗМЕРА (внутри, видит myMap)
    function fixMap() {
        if (typeof myMap !== 'undefined') {
            myMap.container.fitToViewport();
        }
    }

    // 4. СЛУШАЕМ ИЗМЕНЕНИЕ РАЗМЕРА ОКНА (с задержкой 150 мс)
    window.addEventListener('resize', function () {
        // Очищаем предыдущий таймаут, если он есть
        // И устанавливаем новый таймаут, чтобы вызвать fixMap через 150 мс после последнего изменения размера
        clearTimeout(window.resizedFinished);
        window.resizedFinished = setTimeout(function () {
            fixMap();
        }, 150);
    });
}

// ====== ЗАПУСК КАРТЫ (автоматически) ======
if (typeof ymaps !== 'undefined') {
    // Ждём, когда API Яндекс.Карт будет готов, 
    // инициализируем карту с данными из data-атрибутов
    ymaps.ready(function () {
        // Берём данные из data-атрибутов HTML
        var mapBlock = document.getElementById('yandex-map');
        if (mapBlock) {
            // Получаем данные из data-атрибутов
            var address = mapBlock.dataset.address;
            var venueName = mapBlock.dataset.venue;
            var street = mapBlock.dataset.street;
            var city = mapBlock.dataset.city;
            // Инициализируем карту с этими данными 
            initYandexMap(address, venueName, street, city);
        }
    });
}