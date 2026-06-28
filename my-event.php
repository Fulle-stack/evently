<?php

session_start();

// ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

// Массив сокращённых названий месяцев
$months_short = [
    'Jan' => 'Янв',
    'Feb' => 'Фев',
    'Mar' => 'Мар',
    'Apr' => 'Апр',
    'May' => 'Мая',
    'Jun' => 'Июн',
    'Jul' => 'Июл',
    'Aug' => 'Авг',
    'Sep' => 'Сен',
    'Oct' => 'Окт',
    'Nov' => 'Ноя',
    'Dec' => 'Дек'
];

// ПРОВЕРКА ДОСТУПА: только организатор
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'organizer') {
    header('Location: Evently-hub.php');
    exit;
}

// ПОЛУЧАЕМ ТОЛЬКО СОБЫТИЯ ЭТОГО ОРГАНИЗАТОРА
// (в таблице event колонка id — это идентификатор организатора-владельца)
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM event WHERE id = :user_id ORDER BY start_date DESC");
$stmt->execute(['user_id' => $user_id]);
$events = $stmt->fetchAll();

$total_events = count($events);

// Считаем активные события
$active_events = 0;
foreach ($events as $event) {
    if ($event['status'] == 'active') {
        $active_events++;
    }
}

// Реальное число участников по каждому событию (по бронированиям) и суммарно
$participants_by_event = [];
$total_participants = 0;
if ($events) {
    $ids = array_column($events, 'id_event');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $pstmt = $pdo->prepare("SELECT id_event, COUNT(*) AS cnt FROM reservation WHERE id_event IN ($in) GROUP BY id_event");
    $pstmt->execute($ids);
    foreach ($pstmt->fetchAll() as $row) {
        $participants_by_event[$row['id_event']] = (int)$row['cnt'];
        $total_participants += (int)$row['cnt'];
    }
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Evently - Мои события</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="my-event.css">
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
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin')): ?>
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

    <div class="layout-wrapper">

        <aside class="sidebar">
            <div class="sidebar__top">
                <h2 class="sidebar__title">Панель организатора</h2>
                <nav class="sidebar__nav">
                    <a href="my-event.php" class="sidebar__link active">
                        <span class="material-symbols-outlined">calendar_today</span>
                        <span>Мои события</span>
                    </a>
                    <a href="forma-event.php" class="sidebar__link">
                        <span class="material-symbols-outlined">add_circle</span>
                        <span>Создать новое</span>
                    </a>
                    <a href="list-participants.php" class="sidebar__link">
                        <span class="material-symbols-outlined">group</span>
                        <span>Участники</span>
                    </a>
                    <a href="personal-account.php" class="sidebar__link">
                        <span class="material-symbols-outlined">settings</span>
                        <span>Настройки</span>
                    </a>
                </nav>
            </div>
            <div class="sidebar__bottom">
                <a class="border__link" href="logout.php" onclick="return confirm('Вы уверены, что хотите выйти ?')">
                    <button class="btn-logout">
                        <span class="material-symbols-outlined">logout</span>
                        <span>Выйти</span>
                    </button>
                </a>
            </div>
        </aside>

        <main class="main-content">

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <?= htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1 class="page-title">Мои мероприятия</h1>
                    <p class="page-subtitle">Управляйте вашими предстоящими и прошедшими событиями</p>
                </div>
                <a href="forma-event.php" class="sidebar__link">
                    <button class="btn-primary">
                        <span class="material-symbols-outlined">add</span>
                        Создать событие
                    </button>
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <p class="stat-label">Всего событий</p>
                        <p class="stat-value"><?= $total_events ?></p>
                    </div>
                    <div class="stat-icon icon-blue">
                        <span class="material-symbols-outlined">event</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <p class="stat-label">Активные</p>
                        <p class="stat-value"><?= $active_events ?></p>
                    </div>
                    <div class="stat-icon icon-green">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <p class="stat-label">Участников</p>
                        <p class="stat-value"><?= $total_participants ?></p>
                    </div>
                    <div class="stat-icon icon-purple">
                        <span class="material-symbols-outlined">group</span>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-toolbar">
                    <div class="search-box">
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined search-icon">search</span>
                            <input type="text" placeholder="Поиск по названию...">
                        </div>
                        <button class="btn-search">Найти</button>
                    </div>
                    <div class="toolbar-actions">
                        <button class="btn-outline">
                            <span class="material-symbols-outlined">filter_list</span> Фильтры
                        </button>
                        <button class="btn-outline">
                            <span class="material-symbols-outlined">download</span> Экспорт
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>Название события</th>
                                <th>Дата</th>
                                <th>Участники</th>
                                <th>Статус</th>
                                <th class="text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="5" class="empty-table">
                                        У вас пока нет созданных событий
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td>
                                            <div class="event-title-cell">
                                                <div class="event-icon bg-indigo"><span
                                                        class="material-symbols-outlined">code</span></div>
                                                <div>
                                                    <p class="event-name"> <?= htmlspecialchars($event['title']) ?></p>
                                                    <p class="event-location">
                                                        <?= htmlspecialchars($event['city']) ?>,
                                                        <?= htmlspecialchars($event['venue_name']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= date('d', strtotime($event['start_date'])) ?>
                                            <?= $months_short[date('M', strtotime($event['start_date']))] ?>
                                            <?= date('Y', strtotime($event['start_date'])) ?>
                                        </td>
                                        <td>
                                            <div class="participants"><?= $participants_by_event[$event['id_event']] ?? 0 ?> зарег.</div>
                                        </td>
                                        <td>
                                            <?php if ($event['status'] == 'active'): ?>
                                                <span class="status active">Одобрено</span>
                                            <?php elseif ($event['status'] == 'moderation'): ?>
                                                <span class="status moderation">На проверке</span>
                                            <?php else: ?>
                                                <span class="status rejected">Отклонено</span>
                                            <?php endif; ?>
                                        </td>


                                        <td class="actions-cell">

                                            <?php if ($event['registration_open'] == 1): ?>
                                                <a href="toggle-registration.php?id=<?= $event['id_event'] ?>&action=close"
                                                    class="btn-icon" title="Закрыть регистрацию">
                                                    <span class="material-symbols-outlined">lock</span>
                                                </a>
                                            <?php else: ?>
                                                <a href="toggle-registration.php?id=<?= $event['id_event'] ?>&action=open"
                                                    class="btn-icon" title="Открыть регистрацию">
                                                    <span class="material-symbols-outlined">lock_open</span>
                                                </a>
                                            <?php endif; ?>
                                            <a href="list-participants.php?id=<?= (int)$event['id_event'] ?>" class="btn-icon" title="Список участников"><span class="material-symbols-outlined">group</span></a>
                                            <a href="event-page.php?id=<?= (int)$event['id_event'] ?>" class="btn-icon" title="Открыть страницу события"><span class="material-symbols-outlined">open_in_new</span></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="burger-menu.js"></script>
</body>

</html>