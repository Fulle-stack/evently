<?php

session_start();

// Если пользователь не залогинен ИЛИ его роль не 'admin' → отправляем на главную
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: Evently-hub.php');
    exit;
}

require __DIR__ . '/bd.php';


// ОБРАБОТКА КНОПКИ "ОДОБРИТЬ"
// Проверяем, есть ли в адресной строке параметр 'approve'
// Пример ссылки: admin-event.php?approve=15
if (isset($_GET['approve'])) {
    // Берём ID события из параметра и превращаем в число
    $id = (int) $_GET['approve'];

    // Обновляем статус события на 'active' (одобрено)

    $stmt = $pdo->prepare("UPDATE event SET status = 'active' WHERE id_event = :id_event");

    $stmt->execute(['id_event' => $id]);

    // Сохраняем сообщение об успехе в сессию
    $_SESSION['success'] = 'Событие успешно одобрено!';
    // Возвращаем админа обратно на страницу модерации
    header('Location: admin-event.php');
    exit;
}

// ОБРАБОТКА КНОПКИ "ОТКЛОНИТЬ"

// Проверяем, есть ли в адресной строке параметр 'reject'
if (isset($_GET['reject'])) {
    // Берём ID события из параметра
    $id = (int) $_GET['reject'];

    // Обновляем статус события на 'rejected' (отклонено)
    $stmt = $pdo->prepare("UPDATE event SET status = 'rejected' WHERE id_event = :id_event");

    $stmt->execute(['id_event' => $id]);

    // Сохраняем сообщение об ошибке в сессию
    $_SESSION['error'] = 'Событие отклонено!';
    // Возвращаем админа обратно на страницу модерации
    header('Location: admin-event.php');
    exit;
}

// ПОЛУЧАЕМ ВСЕ СОБЫТИЯ НА МОДЕРАЦИИ
// Выбираем только те события, у которых статус = 'moderation' (ожидает проверки)
$stmt = $pdo->prepare("SELECT * FROM event WHERE status = 'moderation' ORDER BY id_event DESC");

$stmt->execute();

$events = $stmt->fetchAll();  // массив событий, ждущих проверки


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

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора: Модерация событий</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="admin-event.css">
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
            <div class="sidebar__header">
                <h2>Admin Panel</h2>
                <p>System Control</p>
            </div>
            <nav class="sidebar__nav">
                <a href="admin-page.php" class="sidebar__link"><span class="material-symbols-outlined">grid_view</span> Панель
                    управления</a>
                <a href="add-user.php" class="sidebar__link"><span class="material-symbols-outlined">person</span> Управление
                    пользователями</a>
                <a href="admin-event.php" class="sidebar__link sidebar__link--active"><span
                        class="material-symbols-outlined active-icon">verified</span> Модерация событий</a>
            </nav>
            <div class="sidebar__footer">
                <a href="data-processing-law.php" class="sidebar__link"><span class="material-symbols-outlined">help</span> Поддержка</a>
                <a href="logout.php" onclick="return confirm('Вы уверены, что хотите выйти?')" class="sidebar__link sidebar__link--danger"><span
                        class="material-symbols-outlined">logout</span> Выход</a>
            </div>
        </aside>

        <main class="main-content">

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

            <div class="content-header">
                <h1>Модерация событий</h1>
                <p>Просмотр и утверждение новых событий, созданных пользователями.</p>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Событие</th>
                                <th>Организатор</th>
                                <th>Дата</th>
                                <th>Статус</th>
                                <th class="text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Если нет событий на модерации -->
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        Нет событий на модерации
                                    </td>
                                </tr>
                            <?php else: ?>
                                <!-- Перебираем все события на модерации -->
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td>
                                            <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
                                            <div class="event-desc"><? htmlspecialchars($event['described']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($event['organizer_name']) ?></td>
                                        <td><?= date('d', strtotime($event['start_date'])) ?>
                                            <?= $months_short[date('M', strtotime($event['start_date']))] ?>
                                            <?= date('Y', strtotime($event['start_date'])) ?>
                                        </td>
                                        <td>
                                            <span class="status-badge"><span class="material-symbols-outlined">pending</span>
                                                Ожидает модерации</span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- Просмотр: ссылка на страницу события с параметром preview -->
                                                <a href="event-page.php?id=<?= htmlspecialchars($event['id_event']) ?>&preview"
                                                    target="_blank" class="btn-action btn-view">
                                                    <span class="material-symbols-outlined">visibility</span> Посмотреть
                                                </a>

                                                <!-- Одобрить -->
                                                <a href="?approve=<?= htmlspecialchars($event['id_event']) ?>"
                                                    class="btn-action btn-approve"
                                                    onclick="return confirm('Одобрить событие?')">
                                                    <span class="material-symbols-outlined">check_circle</span> Одобрить
                                                </a>

                                                <!-- Отклонить -->
                                                <a href="?reject=<?= htmlspecialchars($event['id_event']) ?>"
                                                    class="btn-action btn-reject"
                                                    onclick="return confirm('Отклонить событие?')">
                                                    <span class="material-symbols-outlined">cancel</span> Отклонить

                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="page-btn" disabled>‹</button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">›</button>
                </div>
            </div>

            <footer class="footer">
                <div class="footer__copy">© 2026 Evently. Все права защищены.</div>
                <div class="footer__links">
                    <a href="data-processing-law.php">Политика конфиденциальности</a>
                    <a href="data-processing-law.php">Условия использования</a>
                    <a href="data-processing-law.php">Контакты</a>
                </div>
            </footer>
        </main>
    </div>

</body>

</html>