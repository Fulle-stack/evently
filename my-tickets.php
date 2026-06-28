<?php

// ЗАПУСКАЕМ СЕССИЮ
session_start();

// ЗАПУСКАЕМ СЕССИЮ
require __DIR__ . '/bd.php';

// ПРОВЕРКА: ЗАЛОГИНЕН ЛИ ПОЛЬЗОВАТЕЛЬ?
if (!isset($_SESSION['user_id'])) {
    header('Location:  Evently-hub.php');
    exit;
}

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

// БЕРЕМ ID ПОЛЬЗОВАТЕЛЯ ИЗ СЕССИИ
$user_id = $_SESSION['user_id'];

// запрос к базе данных (получаем билеты пользователя)
$stmt = $pdo->prepare("SELECT 
    tickets.id_tickets, -- Номер билета (например, 101)
    tickets.ticket_number, -- Уникальный код билета (TK-123-456-0)
    event.title, -- Название события (Tech Summit 2026)
    event.photo, -- Ссылка на фото
    event.start_date,  -- Дата события (2026-11-15)
    event.category, -- Категория (tech)
    event.venue_name, -- Место
    event.city, -- Город
    seats.seat_row, -- Ряд места (A, B, C...)
    seats.seat_number -- Номер места (1, 2, 3...)
    FROM tickets -- Начинаем с билетов (основа)
    JOIN event ON tickets.event_id = event.id_event -- Клеим событие к билетам
    JOIN reservation ON tickets.id_payment = reservation.id_payment -- Клеим бронь к билетам
    LEFT JOIN seats ON tickets.seat_id = seats.id_seat -- соединяем билеты с местами по ID места (LEFT JOIN сохраняет билеты без места)
    WHERE reservation.id_participants = :id_participant -- Оставляем только мои билеты
    ORDER BY event.start_date DESC -- Сортируем от новых к старым
");

// Выполняем запрос, подставляя ID пользователя
$stmt->execute(['id_participant' => $user_id]);

// Получаем все билеты
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Evently - Личный кабинет</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="my-tickets.css" />
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
                <!-- Показываем имя пользователя и аватар, если он залогинен -->
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
                    <a href="logout.php" class="logout-btn" onclick="return confirm('Вы уверены, что хотите выйти?')">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="registration.php">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="main-layout">

            <aside class="sidebar">
                <div class="lk-card">
                    <div class="lk-user">
                        <div class="lk-ava"><?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['name'] ?? '?', 0, 1))) ?></div>
                        <div>
                            <div class="lk-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Пользователь') ?></div>
                            <div class="lk-role">Участник</div>
                        </div>
                    </div>
                    <nav class="lk-nav">
                        <a class="lk-link is-active" href="my-tickets.php"><span class="material-symbols-outlined">confirmation_number</span> Мои билеты</a>
                        <a class="lk-link" href="event-search.php"><span class="material-symbols-outlined">search</span> Найти событие</a>
                        <a class="lk-link" href="personal-account.php"><span class="material-symbols-outlined">settings</span> Настройки профиля</a>
                        <div class="lk-sep"></div>
                        <a class="lk-link lk-link--danger" href="logout.php" onclick="return confirm('Вы уверены, что хотите выйти?')"><span class="material-symbols-outlined">logout</span> Выход</a>
                    </nav>
                </div>
            </aside>

            <main class="content">
                <div class="content-panel">

                    <div class="content-header">
                        <h2 class="page-title">Мои билеты</h2>
                        <div class="search-action-bar">
                            <div class="search-box">
                                <span class="material-symbols-outlined search-icon">search</span>
                                <input type="text" class="search-input" placeholder="Поиск билета...">
                            </div>
                            <button class="filter-btn">
                                <span class="material-symbols-outlined">filter_list</span>
                            </button>
                            <button class="btn-primary">Найти</button>
                        </div>
                    </div>

                    <div class="tabs">
                        <button class="tab active">Все события</button>
                        <button class="tab">Предстоящие</button>
                        <button class="tab">Прошедшие</button>
                    </div>

                    <div class="events-grid">

                        <?php if (count($tickets) > 0): ?>

                            <?php foreach ($tickets as $ticket): ?>
                                <div class="event-card">
                                    <div class="card-image-wrapper">
                                        <img class="card-image" src="<?= htmlspecialchars($ticket['photo']) ?>"
                                            alt="<?= htmlspecialchars($ticket['title']) ?>" />
                                        <div class="card-date">
                                            <?= date('d', strtotime($ticket['start_date'])) ?>
                                            <?= $months_ru[date('F', strtotime($ticket['start_date']))] ?>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="card-category text-purple">
                                            <span
                                                class="material-symbols-outlined">school</span><?= htmlspecialchars($category_names[$ticket['category']] ?? $ticket['category']) ?>
                                        </div>
                                        <h3 class="card-title"><?= htmlspecialchars($ticket['title']) ?></h3>
                                        <div class="card-location">
                                            <span
                                                class="material-symbols-outlined">location_on</span><?= htmlspecialchars($ticket['venue_name']) ?>,
                                            <?= htmlspecialchars($ticket['city']) ?>
                                        </div>
                                        <!-- НОВЫЙ БЛОК: показываем ряд и место -->
                                        <?php if (!empty($ticket['seat_row'])): ?>
                                            <!-- Блок отображается ТОЛЬКО для билетов С местом -->
                                            <div class="card-seat">
                                                <span class="material-symbols-outlined">event_seat</span>
                                                Ряд <?= htmlspecialchars($ticket['seat_row']) ?>, Место <?= htmlspecialchars($ticket['seat_number']) ?>
                                            </div>
                                            <?php endif; ?>
                                        <a class="btn-ticket" href="ticket.php?id=<?= (int)$ticket['id_tickets'] ?>" style="text-decoration:none">
                                            <span>Посмотреть билет</span>
                                            <span class="material-symbols-outlined">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="event-card card-empty">
                                <div class="empty-block">
                                    <h3 class="empty-state-title">У вас нет билетов</h3>
                                    <p class="empty-state-description">Нажмите на карточку "Найти событие"</p>
                                </div>
                            </div>

                        <?php endif; ?>
                        <a class="event-link" href="event-search.php">
                             <div class="event-card card-add">
                            <div class="add-icon-wrapper">
                                <span class="material-symbols-outlined">add</span>
                            </div>
                            <h3 class="add-title">Найти событие</h3>
                            <p class="add-desc">Посмотрите афишу и найдите интересные мероприятия</p>
                        </div>
                        </a>
                       

                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>© 2026 Evently. Все права защищены.</p>
            <div class="footer-links">
                <a href="Evently-hub.php">О нас</a>
                <a href="data-processing-law.php">Контакты</a>
                <a href="data-processing-law.php">Помощь</a>
            </div>
        </div>
    </footer>

    <script src="burger-menu.js"></script>
</body>

</html>