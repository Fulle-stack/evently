<?php

session_start();

// Только админ
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: Evently-hub.php');
    exit;
}

require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

// Получаем ID пользователя из параметров URL для редактирования 
// (например, edit-user.php?id=123)
$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$role = $_GET['role'] ?? '';

// Проверяем, что ID пользователя и роль переданы корректно
// Роль должна быть одной из допустимых: admin, organizer, participant
if ($userId <= 0 || !in_array($role, ['admin', 'organizer', 'participant'])) {
    $_SESSION['error'] = 'Некорректные параметры для редактирования пользователя.';
    header('Location: admin-page.php');
    exit;
}

// Выбираем таблицу в зависимости от роли
if ($role === 'admin') {
    $table = 'admins';
    $idColumn = 'id';
} elseif ($role === 'organizer') {
    $table = 'organizers';
    $idColumn = 'id';
} else {
    $table = 'participants';
    $idColumn = 'id_participants';
}

// Берём таблицу и имя первичного ключа
$map = role_table($role);
$table = $map['table'];
$idColumn = $map['pk'];

// Подготовка SQL-запроса для получения данных пользователя по ID
$stmt = $pdo->prepare("SELECT * FROM $table WHERE $idColumn = :id");

// Выполнение запроса с передачей ID пользователя
$stmt->execute(['id' => $userId]);

// Получение данных пользователя
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Если пользователь не найден, перенаправляем обратно с ошибкой
if (!$user) {
    $_SESSION['error'] = 'Пользователь не найден.';
    header('Location: admin-page.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать пользователя - Evently</title>
    <link rel="stylesheet" href="edit-user.css">
    <link rel="stylesheet" href="alerts.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
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

                <!-- Мои события (только для организатора и админа) -->
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin')): ?>
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
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin')): ?>
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
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar__header">
                <h2 class="sidebar__subtitle">Admin Panel</h2>
                <p class="sidebar__title">System Control</p>
            </div>
            <nav class="sidebar__nav">
                <a href="admin-page.php" class="sidebar__link">
                    <span class="material-symbols-outlined">dashboard</span>
                    Панель управления
                </a>
                <a href="admin-page.php" class="sidebar__link active">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">group</span>
                    Управление пользователями
                </a>
                <a href="admin-event.php" class="sidebar__link">
                    <span class="material-symbols-outlined">event_available</span>
                    Модерация событий
                </a>
            </nav>
            <div class="sidebar__footer">
                <a href="data-processing-law.php" class="sidebar__link">
                    <span class="material-symbols-outlined">help</span>
                    Поддержка
                </a>
                <a href="logout.php" class="sidebar__link logout"
                    onclick="return confirm('Вы уверены, что хотите выйти?')">
                    <span class="material-symbols-outlined">logout</span>
                    Выход
                </a>
            </div>
        </aside>

        <main class="main">
            <div class="breadcrumbs">
                <a href="admin-page.php">Управление пользователями</a>
                <span class="material-symbols-outlined">chevron_right</span>
                <span>Редактировать пользователя</span>
            </div>

            <div class="page-header">
                <h1 class="page-title">Редактировать пользователя</h1>
                <p class="page-subtitle">Внесите изменения в информацию о пользователе.</p>
            </div>

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

            <div class="card">
                <div class="card__accent"></div>
                <form class="form" action="edit-user-handler.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($user[$idColumn]) ?>" />
                    <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>" />
                    <div class="form__grid">
                        <div class="form__section">
                            <h3 class="form__section-title">
                                <span class="material-symbols-outlined">person</span>
                                Личные данные
                            </h3>

                            <div class="form__group">
                                <label for="fullName">Полное имя</label>
                                <input type="text" id="fullName" name="name" placeholder="Иван Иванов" value="<?= htmlspecialchars($user['name']) ?>" />
                            </div>

                            <div class="form__group">
                                <label for="email">Email адрес</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">mail</span>
                                    <input type="email" id="email" name="email" placeholder="ivan@example.com" value="<?= htmlspecialchars($user['email']) ?>" />
                                </div>
                            </div>

                            <div class="form__group">
                                <label for="password">Пароль</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">lock</span>
                                    <input type="password" id="password" name="password" placeholder="••••••••" />
                                </div>
                                <span class="form__hint">Оставьте пустым, чтобы сохранить старый пароль.</span>
                            </div>
                        </div>
                    </div>

                    <div class="form__actions">
                        <button type="button" class="btn-cancel">Отмена</button>
                        <button type="submit" class="btn-submit">
                            <span class="material-symbols-outlined">save</span>
                            Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>