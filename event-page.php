<?php

session_start();

// Страница события — публичная витрина, доступна всем (покупка требует входа).
require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

// Устанавливаем русскую локаль для дат
setlocale(LC_TIME, 'ru_RU.UFT-8');

// Массив для перевода категорий
$category_names = [
    'education' => 'Образование',
    'business' => 'Бизнес',
    'sports' => 'Спорт',
    'music' => 'Музыка',
    'art' => 'Искусство',
    'texh' => 'Технологии',
    'entertainment' => 'Развлечение'
];

// Массив перевода месяцев (английский → русский)
$months_ru = [
    'January' => 'Января',
    'February' => 'Февраля',
    'March' => 'Марта',
    'April' => 'Апреля',
    'May' => 'Мая',
    'June' => 'Июня',
    'July' => 'Июля',
    'August' => 'Августа',
    'September' => 'Сентября',
    'October' => 'Октября',
    'November' => 'Ноября',
    'December' => 'Декабря'
];

// Массив для перевода категорий
$event_type_names = [
    'conference' => 'Конференция',
    'masterclass' => 'Мастер-класс',
    'webinar' => 'Вебинар',
    'exhibition' => 'Выставка',
    'concert' => 'Концерт',
    'festival' => 'Фестиваль',
    'meetup' => 'Встреча',
    'workshop' => 'Воркшоп',
    'lecture' => 'Лекция',
    'hackathon' => 'Хакатон'
];


// Получаем ID события из URL
$event_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Если ID нет или он неверный — перенаправляем на главную
if ($event_id <= 0) {
    header('Location: event-search.php');
    exit;
}

// Проверяем, админ ли смотрит предпросмотр неопубликованного события
// 1. isset($_GET['preview']) - есть ли в адресной строке &preview
// 2. isset($_SESSION['user_role']) - залогинен ли пользователь (есть ли роль в сессии)
// 3. $_SESSION['user_role'] === 'admin' - имеет ли пользователь права администратора
$isAdminPreview = isset($_GET['preview']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Загружаем событие из БД
if ($isAdminPreview) {
    // Админ: показываем любое событие (без проверки статуса)
    $stmt = $pdo->prepare("SELECT * FROM event WHERE id_event = :id");
} else {
    // Обычный пользователь: только активные
    $stmt = $pdo->prepare("SELECT * FROM event WHERE id_event = :id AND status = 'active'");
}

// Подставляем значение $event_id (6) вместо плейсхолдера :id
$stmt->execute(['id' => $event_id]);

// Превращаем результат запроса в массив $event, где ключи = названия колонок
$event = $stmt->fetch(PDO::FETCH_ASSOC);

// Если событие не найдено — перенаправляем на главную
if (!$event) {
    header('Location: event-search.php');
    exit;
}

// Получаем все места для этого события

// 1. Запрашиваем все места, которые принадлежат этому событию
// Сортировка: сначала по ряду (A, B, C...), потом по номеру места
$stmtSeats = $pdo->prepare("SELECT * FROM seats WHERE event_id = :event_id ORDER BY seat_row, seat_number");
$stmtSeats->execute(['event_id' => $event['id_event']]);
$seats = $stmtSeats->fetchAll(); // массив всех мест

// 2. Группируем места по рядам (A, B, C...)
$seats_by_row = [];
foreach ($seats as $seat) {
    // $seat['seat_row'] - узнаём букву ряда у текущего места (например 'A')
// $seats_by_row['A'] - находим массив в котором хранятся места ряда A
// [] - добавляем новое место в конец этого массива
// = $seat - добавляем текущее место
    $seats_by_row[$seat['seat_row']][] = $seat;  // кладём место в его ряд
}

// 3. Считаем свободные места
$freeSeatsCount = 0;
foreach ($seats as $seat) {
    if ($seat['status'] === 'free') {
        $freeSeatsCount++;
    }
}

// Находим самую дешёвую цену (для "от X ₽")
$minPrice = $event['price'];
if (!empty($seats)) {
    $prices = array_column($seats, 'price'); // собираем все цены
    $minPrice = min($prices);
}

// Ценовые зоны: уникальные цены по возрастанию → индекс зоны (цвет) 0,1,2…
$zonePrices = array_values(array_unique(array_map('intval', array_column($seats, 'price'))));
sort($zonePrices);
$priceZone = [];                       // цена => индекс зоны
foreach ($zonePrices as $i => $p) {
    $priceZone[$p] = $i;
}
// Самый правый номер места — для выравнивания рядов по сетке (видны проходы)
$maxSeatNum = 0;
foreach ($seats as $seat) {
    if ((int)$seat['seat_number'] > $maxSeatNum) $maxSeatNum = (int)$seat['seat_number'];
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Evently - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="event-page.css">
    <link rel="stylesheet" href="alerts.css">
    <link rel="stylesheet" href="ui-polish.css">
</head>

<body>

    <header class="header">
        <div class="header__left">
            <a href="Evently-hub.php" class="logo">
                <span class="logo__icon material-symbols-outlined">event_available</span>
                <span class="logo__text">Evently</span>
            </a>
            <nav class="nav">
                <!-- Ссылка на события (для всех) -->
                <a href="event-search.php" class="nav__link">События</a>

                <!-- Мои билеты (только для авторизованных) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my-tickets.php" class="nav__link">Мои билеты</a>
                <?php endif; ?>

                <!-- Мои события (только для организатора и админа) -->
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin')): ?>
                    <a href="my-event.php" class="nav__link">Мои события</a>
                    <a href="registration.php" class="nav__link">Организаторам</a>
                <?php endif; ?>

                <!-- Ссылки только для администратора -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin-event.php" class="nav__link">Модерация</a>
                    <a href="admin-page.php" class="nav__link">Пользователи</a>
                <?php endif; ?>

                <a href="data-processing-law.php" class="nav__link">Помощь</a>
            </nav>
        </div>

        <div class="header__right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Кнопка выхода -->

                <div class="user-profile">
                    <div class="teg-name">
                        <?= htmlspecialchars($_SESSION['name'] ?? 'Пользователь'); ?>
                    </div>
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=100&h=100&auto=format&fit=crop"
                        alt="User" class="nav-avatar">
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-login">Вход</a>
                <a href="registration.php" class="btn-login">Регистрация</a>
            <?php endif; ?>

            <button class="burger">
                <span></span><span></span><span></span>
            </button>

            <!-- Мобильное меню -->
            <nav class="menu">
                <a href="event-search.php">События</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my-tickets.php">Мои билеты</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer')): ?>
                    <a href="my-event.php">Мои события</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin-event.php">Модерация</a>
                    <a href="admin-page.php">Пользователи</a>
                <?php endif; ?>
                <a href="data-processing-law.php">Помощь</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="link-logout"
                        onclick="return confirm('Вы уверены, что хотите выйти?')">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="registration.php">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success">
            <?= $_SESSION['success'];
            unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error">
            <?= $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    <main class="main-content">
        <div class="container">
            <div class="hero">
                <img src="<?= htmlspecialchars($event['photo']) ?>" alt="<?= htmlspecialchars($event['title']) ?>"
                    class="hero__image">
            </div>

            <div class="layout">
                <div class="content-left">
                    <div class="tags">
                        <span
                            class="tag tag-primary"><?= htmlspecialchars($category_names[$event['category']] ?? $event['category']) ?></span>
                        <span
                            class="tag tag-secondary"><?= htmlspecialchars($event_type_names[$event['event_type']] ?? $event['event_type']) ?></span>
                    </div>

                    <h1 class="page-title"><?= htmlspecialchars($event['title']) ?></h1>
                    <p class="page-subtitle"><?= htmlspecialchars($event['described']) ?></p>

                    <div class="info-cards">
                        <div class="card">
                            <div class="card-icon">
                                <span class="material-symbols-outlined">calendar_month</span>
                            </div>
                            <div class="card-text">
                                <h3>Дата и время</h3>
                                <p class="primary-text">
                                    <?= date('d', strtotime($event['start_date'])) ?>
                                    <?= $months_ru[date('F', strtotime($event['start_date']))] ?>
                                    <?= date('Y', strtotime($event['start_date'])) ?>
                                </p>
                                <p class="secondary-text">
                                    <?= date('H:i', strtotime($event['start_time'])) ?> -
                                    <?= date('H:i', strtotime($event['end_time'])) ?>
                                </p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-icon">
                                <span class="material-symbols-outlined">location_on</span>
                            </div>
                            <div class="card-text">
                                <h3>Местоположение</h3>
                                <p class="primary-text">
                                    <?= htmlspecialchars($event['venue_name']) ?>
                                </p>
                                <p class="secondary-text">
                                    <?= htmlspecialchars($event['street']) ?>,
                                    <?= htmlspecialchars($event['city']) ?>,
                                    <?= htmlspecialchars($event['country']) ?>,
                                    <?php if ($event['floor']): ?>этаж <?= htmlspecialchars($event['floor']) ?>,
                                    <?php endif; ?>
                                    <?php if ($event['entrance']): ?>подъезд
                                        <?= htmlspecialchars($event['entrance']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="organizer">
                        <div class="organizer-info">
                            <span class="label">Организатор</span>
                            <span class="name"><?= htmlspecialchars($event['organizer_name']) ?></span>
                        </div>
                        <a href="data-processing-law.php" class="contact-link">Связаться</a>
                    </div>

                    <!-- Спикеры (если есть) -->
                    <?php if (!empty($event['speakers'])): ?>
                        <div class="about-section">
                            <h2>Спикеры</h2>
                            <p><?= nl2br(htmlspecialchars($event['speakers'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="about-section">
                        <h2>О событии</h2>
                        <p><?= nl2br(htmlspecialchars($event['described'])) ?></p>
                    </div>

                    <!-- Яндекс.Карта -->
                    <div class="map-placeholder">
                        <div id="yandex-map"
                            data-address="<?= htmlspecialchars($event['street']) ?>, <?= htmlspecialchars($event['city']) ?>, <?= htmlspecialchars($event['country']) ?>"
                            data-venue="<?= htmlspecialchars($event['venue_name']) ?>"
                            data-street="<?= htmlspecialchars($event['street']) ?>"
                            data-city="<?= htmlspecialchars($event['city']) ?>">
                        </div>
                    </div>
                </div>

                <aside class="sidebar">
                    <div class="ticket-card">
                        <form action="buy-ticket.php" method="POST" id="buyForm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="event_id" value="<?= $event['id_event'] ?>">
                            <input type="hidden" name="selected_seats" id="selectedSeatsInput">

                            <div class="ticket-header">
                                <span class="ticket-label">Стоимость</span>
                                <div>
                                    <span class="ticket-price" id="ticketPrice">
                                        <?= $event['price'] ?> ₽
                                    </span>
                                    <span class="ticket-label" style="font-size: 12px;">за билет</span>
                                </div>
                            </div>
                            <?php if (count($seats) > 0): ?> <!-- ЕСЛИ есть места в БД -->
                                <!-- СХЕМА ЗАЛА -->
                                <div class="hall-schema">
                                    <div class="hall-viewport">
                                        <div class="stage-screen">СЦЕНА</div>
                                        <div class="hall-rows">
                                            <?php foreach ($seats_by_row as $row => $row_seats): ?>
                                                <div class="seat-row">
                                                    <span class="row-letter"><?= htmlspecialchars($row) ?></span>
                                                    <div class="row-seats" style="grid-template-columns: repeat(<?= $maxSeatNum ?>, 1fr);">
                                                        <?php foreach ($row_seats as $seat): ?>
                                                            <?php
                                                                $isFree = $seat['status'] === 'free';
                                                                $zone   = $priceZone[(int)$seat['price']] % 3; // 0..2
                                                                $tip = 'Ряд ' . $seat['seat_row'] . ' · Место ' . $seat['seat_number'] . ' · ' . number_format((int)$seat['price'], 0, '', ' ') . ' ₽';
                                                            ?>
                                                            <button type="button"
                                                                class="seat <?= $isFree ? 'free zone-c' . $zone : 'taken' ?>"
                                                                style="grid-column: <?= (int)$seat['seat_number'] ?>;"
                                                                data-id="<?= htmlspecialchars($seat['id_seat']) ?>"
                                                                data-row="<?= htmlspecialchars($seat['seat_row']) ?>"
                                                                data-num="<?= htmlspecialchars($seat['seat_number']) ?>"
                                                                data-price="<?= htmlspecialchars($seat['price']) ?>"
                                                                title="<?= htmlspecialchars($tip) ?>"
                                                                aria-label="<?= htmlspecialchars($tip . ($isFree ? '' : ', занято')) ?>"
                                                                <?= $isFree ? '' : 'disabled' ?>>
                                                                <?= htmlspecialchars($seat['seat_number']) ?>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <span class="row-letter"><?= htmlspecialchars($row) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <!-- Легенда: ценовые зоны и статусы -->
                                    <div class="hall-legend">
                                        <?php foreach ($zonePrices as $z => $zp): ?>
                                            <span class="legend-item">
                                                <span class="legend-dot zone-c<?= $z % 3 ?>"></span>
                                                <?= number_format((int)$zp, 0, '', ' ') ?> ₽
                                            </span>
                                        <?php endforeach; ?>
                                        <span class="legend-item"><span class="legend-dot is-selected"></span> Выбрано</span>
                                        <span class="legend-item"><span class="legend-dot is-taken"></span> Занято</span>
                                    </div>
                                </div>

                                <!-- ВЫБРАННЫЕ МЕСТА -->
                                <div class="selected-seats-list">
                                    <div class="selected-header">Выбранные места:</div>
                                    <div id="seatsContainer" class="seats-container">Нет выбранных мест</div>
                                </div>

                                <!-- ИНДИКАТОР СВОБОДНЫХ МЕСТ -->
                                <div class="spots-left">
                                    <span class="status-dot <?= $freeSeatsCount > 0 ? 'green' : 'red' ?>"></span>
                                    <span>Свободных мест: <strong><?= $freeSeatsCount ?></strong></span>
                                </div>

                                <!-- ИТОГО ДЛЯ СХЕМЫ ЗАЛА -->
                                <div class="ticket-total">
                                    <span class="ticket-label">Итого:</span>
                                    <span class="ticket-price" id="totalPrice">0 ₽</span>
                                </div>

                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="btn-register" style="display:block;text-align:center;text-decoration:none">Войдите, чтобы купить</a>
                            <?php elseif (($_SESSION['user_role'] ?? '') !== 'participant'): ?>
                                <button type="button" class="btn-register registration-closed" disabled>Покупка доступна участникам</button>
                            <?php elseif ($event['registration_open'] == 1): ?>
                                <!-- если есть места - кнопка отключена, пока не выбрано место -->
                                <button type="submit" id="buyBtn" class="btn-register" disabled>Купить билеты</button>
                            <?php else: ?>
                                <button type="button" class="btn-register registration-closed" disabled>Регистрация закрыта</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="btn-register" style="display:block;text-align:center;text-decoration:none">Войдите, чтобы купить</a>
                                <?php elseif (($_SESSION['user_role'] ?? '') !== 'participant'): ?>
                                <button type="button" class="btn-register registration-closed" disabled>Покупка доступна участникам</button>
                                <?php elseif ($event['registration_open'] == 1): ?>
                                <!-- СТАРЫЙ СПОСОБ (если мест нет) -->
                                <div class="buy-row">
                                    <button type="submit" class="btn-register">Купить билет</button>
                                    <select name="quantity" class="quantity__select" id="oldQuantity">
                                        <option value="1">1 билет</option>
                                        <option value="2">2 билета</option>
                                        <option value="3">3 билета</option>
                                        <option value="4">4 билета</option>
                                        <option value="5">5 билетов</option>
                                    </select>
                                </div>
                                <div class="ticket-total" style="margin-top: 15px;">
                                    <span class="ticket-label">Итого:</span>
                                    <span class="ticket-price" id="oldTotalPrice">0 ₽</span>
                                </div>
                                 <?php else: ?>
                                    <button type="button" class="btn-register registration-closed" disabled>Регистрация закрыта</button>
                                    <?php endif; ?>
                            <?php endif; ?>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-content">
            <p>© 2026 Evently. Все права защищены.</p>
            <div class="footer-links">
                <a href="data-processing-law.php">Политика конфиденциальности</a>
                <a href="data-processing-law.php">Условия использования</a>
            </div>
        </div>
    </footer>
    <script src="burger-menu.js"></script>
    <script>
        // 1. МАССИВ ДЛЯ ХРАНЕНИЯ ВЫБРАННЫХ МЕСТ
        let selected = [];

        // 2. НАХОДИМ ВСЕ СВОБОДНЫЕ КНОПКИ МЕСТ
        let seats = document.querySelectorAll('.seat.free');

        // 3. НАСТРАИВАЕМ КАЖДУЮ КНОПКУ
        for (let i = 0; i < seats.length; i++) {
            let btn = seats[i];

            // Запоминаем данные с кнопки (ID, ряд, номер, цена)
            let seatId = btn.dataset.id;
            let seatRow = btn.dataset.row;
            let seatNum = btn.dataset.num;
            let seatPrice = parseInt(btn.dataset.price);

            // Что делать при клике на кнопку
            btn.onclick = function () {
                // Сколько уже выбрано мест
                let currentCount = selected.length;

                // Проверяем, выбрано ли уже это место
                let isSelected = selected.some(seat => seat.id == seatId);

                // Если место НЕ выбрано И уже есть 5 мест → ошибка
                if (!isSelected && currentCount >= 5) {
                    alert('За один раз можно заказать не более 5 билетов');
                    return;
                }

                if (isSelected) {
                    // Убираем место из массива
                    selected = selected.filter(seat => seat.id != seatId);
                    this.classList.remove('is-selected');
                } else {
                    // Добавляем место в массив
                    selected.push({
                        // ключ - значение
                        id: seatId,
                        row: seatRow,
                        num: seatNum,
                        price: seatPrice
                    });
                    this.classList.add('is-selected');
                }
                // 4. СОЗДАЁМ СПИСОК ВЫБРАННЫХ МЕСТ
                let listHtml = '';
                let total = 0;
                for (let j = 0; j < selected.length; j++) {
                    listHtml += '<div>Ряд ' + selected[j].row + ', место ' + selected[j].num + ' — ' + selected[j].price + ' ₽</div>';
                    total += selected[j].price;
                }

                // 5. ПОКАЗЫВАЕМ ОБНОВЛЁННЫЕ ДАННЫЕ НА СТРАНИЦЕ
                document.getElementById('seatsContainer').innerHTML = listHtml || 'Нет выбранных мест';
                document.getElementById('totalPrice').innerHTML = total + ' ₽';
                document.getElementById('buyBtn').disabled = (selected.length === 0);
            };
        }

        // 6. ПЕРЕД ОТПРАВКОЙ ФОРМЫ — СОХРАНЯЕМ ВЫБРАННЫЕ МЕСТА
        let form = document.getElementById('buyForm');
        if (form) {
            form.onsubmit = function (event) {
                if (selected.length === 0) {
                    event.preventDefault(); // ← отменяем отправку
                    alert('Выберите места!');
                    return false; // ← явно говорим "отменить отправку"
                }
                // Превращаем массив в JSON и кладём в скрытое поле
                document.getElementById('selectedSeatsInput').value = JSON.stringify(selected);
            };
        }

        // JS ДЛЯ СТАРОЙ ФОРМЫ (КОГДА НЕТ МЕСТ)
        // =============================================

        // 1. НАХОДИМ ВЫПАДАЮЩИЙ СПИСОК С КОЛИЧЕСТВОМ БИЛЕТОВ
        //    В HTML это <select id="oldQuantity">
        let oldSelect = document.getElementById('oldQuantity');

        // 2. НАХОДИМ БЛОК "ИТОГО", КУДА БУДЕМ ВЫВОДИТЬ СУММУ
        //    В HTML это <span id="oldTotalPrice">
        let oldTotalSpan = document.getElementById('oldTotalPrice');

        // 3. БЕРЁМ ЦЕНУ ОДНОГО БИЛЕТА ИЗ БАЗЫ ДАННЫХ
        //    PHP вставляет число (например, 2500) прямо в JavaScript
        let oldPricePerTicket = <?= $event['price'] ?>;

        // 4. ФУНКЦИЯ, КОТОРАЯ СЧИТАЕТ ОБЩУЮ СУММУ
        function updateOldTotal() {
            // 4.1. Берём выбранное значение (1, 2, 3, 4 или 5)
            let count = parseInt(oldSelect.value);

            // 4.2. Умножаем количество на цену одного билета
            let total = count * oldPricePerTicket;

            // 4.3. Показываем сумму в блоке "Итого"
            oldTotalSpan.innerHTML = total + ' ₽';
        }

        // 5. Если на странице есть старая форма — настраиваем её
        // (старой формы может не быть, если есть места в зале)
        if (oldSelect && oldTotalSpan) {
            // 6. При изменении количества билетов — обновляем сумму
            oldSelect.onchange = updateOldTotal;

            // 7. Вызываем функцию один раз при загрузке страницы, чтобы показать сумму для начального количества
            updateOldTotal();
        }
    </script>
    <!-- Подключение API и запуск -->
    <script src="https://api-maps.yandex.ru/2.1/?apikey=48ace834-f8da-467d-83bb-89fbb1ed2257&lang=ru_RU"></script>
    <script src="yandex-map.js"></script>
</body>

</html>