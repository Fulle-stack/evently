<?php

session_start();

// Каталог мероприятий — публичная витрина, доступен всем (покупка требует входа).
// Переключает язык дат на русский
setlocale(LC_TIME, 'ru_RU.UTF-8');

require __DIR__ . '/bd.php';

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
    'Jan' => 'Янв',
    'Feb' => 'Фев',
    'Mar' => 'Мар',
    'Apr' => 'Апр',
    'May' => 'Май',
    'Jun' => 'Июн',
    'Jul' => 'Июл',
    'Aug' => 'Авг',
    'Sep' => 'Сен',
    'Oct' => 'Окт',
    'Nov' => 'Ноя',
    'Dec' => 'Дек'
];

// ПОЛУЧАЕМ ПАРАМЕТРЫ ИЗ URL

// $search — что пользователь ввёл в поле поиска
$search = trim($_GET['search'] ?? '');

// $category — какую категорию выбрал (music, education, ...)
$category = $_GET['category'] ?? '';

// СТРОИМ ДИНАМИЧЕСКИЙ SQL-ЗАПРОС
// Базовая часть — только активные события

$sql = "SELECT * FROM event WHERE status = 'active'";

// массив для подстановки значений
$params = []; 

// Если есть поисковый запрос — добавляем LIKE (поиск по названию)
if (!empty($search)) {
    // Добавляем условие для поиска по названию
    $sql .= " AND title LIKE :search";
    $params['search'] = "%$search%"; // % означает "любые символы вокруг"
}

// Если выбрана категория — добавляем условие для категории
// Проверяем, что категория не пустая и существует в нашем массиве категорий
if (!empty($category) && isset($category_names[$category])) {
    // Добавляем условие для фильтрации по категории
    $sql .= " AND category = :category";
    $params['category'] = $category; // передаём ключ категории (например, 'music')

}

// сортировка по дате начала (сначала новые)
$sql .= " ORDER BY start_date DESC";

// ПОДГОТАВЛИВАЕМ И ВЫПОЛНЯЕМ ЗАПРОС
// Используем подготовленные выражения для безопасности
$stmt = $pdo->prepare($sql);

// Выполняем запрос с параметрами
$stmt->execute($params);

// Получаем результаты 
// $events — массив событий, который будем выводить на странице
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evently - Поиск событий</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="event-search.css">
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
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer')): ?>
                    <a href="my-event.php" class="nav__link">Мои события</a>
                    <a href="registration.php" class="nav__link">Организаторам</a>
                <?php endif; ?>

                <!-- Ссылки только для администратора -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin-event.php" class="nav__link">Модерация</a>
                    <a href="admin-page.php" class="nav__link">Пользователи</a>
                <?php endif; ?>

            </nav>
        </div>

        <div class="header__right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Кнопка выхода -->
                <a href="logout.php" class="logout-btn" onclick="return confirm('Вы уверены, что хотите выйти?')">Выйти</a>
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
                    <a href="logout.php" class="link-logout">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="registration.php">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="main-wrapper">
        <section class="hero-section">
            <h1 class="hero-title">Найдите идеальное мероприятие</h1>
            <p class="hero-subtitle">Концерты, мастер-классы, выставки и многое другое в вашем городе</p>

            <form class="search-container" action="event-search.php" method="GET">
                <div class="search-input-box">
                    <span class="material-symbols-outlined search-icon">search</span>
                    <input type="text" name="search" class="search-input"
                        placeholder="Поиск событий, концертов, выставок..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-search">Искать</button>
            </form>
        </section>

        <div class="content-layout">
            <aside class="sidebar">
                <div class="filter-card">
                    <div class="filter-header">
                        <h3 class="filter-title">Фильтры</h3>
                        <button class="btn-reset">Сбросить</button>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Город</label>
                        <select class="filter-select">
                            <option>Москва</option>
                            <option>Санкт-Петербург</option>
                            <option>Казань</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Категория</label>
                        <div class="checkbox-list">
                            <a href="event-search.php" class="checkbox-item" <?= empty($category) ? 'active' : '' ?>>
                                Все категории
                            </a>
                            <?php foreach ($category_names as $key => $name): ?>
                                <a href="event-search.php?category=<?= $key ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="checkbox-item <?= ($category === $key) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($name) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Дата</label>
                        <div class="calendar-widget">
                            <div class="calendar-header">
                                <span class="material-symbols-outlined nav-icon" id="prev-month">chevron_left</span>
                                <span class="calendar-month" id="month-display">Октябрь 2023</span>
                                <span class="material-symbols-outlined nav-icon" id="next-month">chevron_right</span>
                            </div>
                            <div class="calendar-weekdays">
                                <span>Пн</span><span>Вт</span><span>Ср</span><span>Чт</span><span>Пт</span><span>Сб</span><span>Вс</span>
                            </div>
                            <div class="calendar-days" id="calendar-days">
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="events-section">
                <div class="events-header">
                    <h2 class="events-title">Популярные события</h2>
                    <div class="sort-box">
                        <span class="sort-label">Сортировать по:</span>
                        <select class="sort-select">
                            <option>Рекомендованные</option>
                            <option>Сначала новые</option>
                        </select>
                    </div>
                </div>

                <div class="events-grid">
                    <?php if (empty($events)): ?>
                        <!-- Если событий нет, показываем сообщение -->
                        <p class="no-events">Пока нет активных событий. Загляните позже!</p>
                    <?php else: ?>
                        <!-- Если события есть, перебираем их в цикле -->
                        <?php foreach ($events as $event): ?>
                            <!-- КАРТОЧКА ОДНОГО СОБЫТИЯ (вся кликабельна) -->
                            <a class="event-card event-card--link" href="event-page.php?id=<?= (int)$event['id_event'] ?>">
                                <!-- БЛОК С ИЗОБРАЖЕНИЕМ -->
                                <div class="card-img-wrapper">
                                    <!-- Фото события (из БД) -->
                                    <img src="<?= htmlspecialchars($event['photo']) ?>"
                                        alt="<?= htmlspecialchars($event['title']) ?>" class="card-img" />

                                    <!-- Бейдж с датой (день и месяц) -->
                                    <div class="card-date-badge"><span class="badge-month">
                                            <?= $months_ru[date('M', strtotime($event['start_date']))] ?>
                                        </span><span class="badge-day"><?= date('d', strtotime($event['start_date'])) ?>
                                        </span></div>
                                    <!-- Бейдж с категорией -->
                                    <div class="card-category-badge bg-blue">
                                        <?= htmlspecialchars($category_names[$event['category']] ?? $event['category']) ?>
                                    </div>
                                </div>
                                <!-- БЛОК С ТЕКСТОВОЙ ИНФОРМАЦИЕЙ -->
                                <div class="card-content">
                                    <h3 class="card-title"><?= htmlspecialchars($event['title']) ?></h3>
                                    <!-- Время проведения -->
                                    <div class="card-info"><span class="material-symbols-outlined info-icon">schedule</span>
                                        <?= date('H:i', strtotime($event['start_time'])) ?>
                                    </div>
                                    <!-- Место проведения (город + площадка) -->
                                    <div class="card-info"><span class="material-symbols-outlined info-icon">location_on</span>
                                        <?= htmlspecialchars($event['city']) ?>,
                                        <?= htmlspecialchars($event['venue_name']) ?>
                                    </div>
                                    <span class="btn-more">Подробнее</span>
                                </div>
                            </a>
                        <?php endforeach; ?> <!-- закрывает цикл foreach -->
                    <?php endif; ?> <!-- закрывает условие if -->
                </div>

            </section>
        </div>
    </main>

    <script src="burger-menu.js"></script>
    <script src="calendar.js"></script>
</body>

</html>