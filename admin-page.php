<?php
session_start();

// Только админ
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: Evently-hub.php');
    exit;
}

// Подключаемся к базе данных
require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

// Собираем всех пользователей из трёх таблиц
$allUsers = [];

// Получаем админов
$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM admins");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($admins as $user) {
    $user['table_role'] = 'admin';
    $allUsers[] = $user;
}

// Получаем организаторов
$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM organizers");
$organizers = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($organizers as $user) {
    $user['table_role'] = 'organizer';
    $allUsers[] = $user;
}

// Получаем участников
$stmt = $pdo->query("SELECT id_participants as id, name, email, role, created_at FROM participants");
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($participants as $user) {
    $user['table_role'] = 'participant';
    $allUsers[] = $user;
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора: Пользователи | Evently</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="admin-page.css">
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

                <!-- Мои события (только для организатора и админа) -->
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer')): ?>
                    <a href="my-event.php" class="nav__link">Мои события</a>
                    <a href="registration.php" class="nav__link">Организаторам</a>
                <?php endif; ?>

                <!-- Ссылки только для администратора -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin-event.php" class="nav__link">Модерация</a>
                    <a href="admin-page.php" class="nav__link">Пользователи</a>
                    <a href="admin-reports.php" class="nav__link">Отчёты</a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="header__right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Кнопка выхода -->
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

    <div class="app-wrapper">

        <aside class="sidebar">
            <div class="sidebar__header">
                <h2>Admin Panel</h2>
                <p>System Control</p>
            </div>
            <nav class="sidebar__nav">
                <a href="admin-page.php" class="sidebar__link sidebar__link--active">
                    <span class="material-symbols-outlined">grid_view</span> Панель управления
                </a>
                <a href="add-user.php" class="sidebar__link">
                    <span class="material-symbols-outlined">person</span> Управление пользователями
                </a>
                <a href="admin-event.php" target="_blank" class="sidebar__link">
                    <span class="material-symbols-outlined">verified</span> Модерация событий
                </a>
                <a href="admin-reports.php" class="sidebar__link">
                    <span class="material-symbols-outlined">monitoring</span> Отчёты
                </a>
            </nav>
            <div class="sidebar__footer">
                <a href="data-processing-law.php" class="sidebar__link">
                    <span class="material-symbols-outlined">help</span> Поддержка
                </a>
                <a href="logout.php" class="sidebar__link sidebar__link--danger" onclick="return confirm('Вы уверены, что хотите выйти?')">
                    <span class="material-symbols-outlined">logout</span> Выход
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="breadcrumbs">
                <a href="Evently-hub.php">Главная</a>
                <span class="material-symbols-outlined">chevron_right</span>
                <span>Панель администратора</span>
            </div>

            <div class="page-header">
                <h1 class="page-title">Пользователи</h1>
                <a href="add-user.php" class="btn-primary">
                    <span class="material-symbols-outlined">person_add</span>
                    Добавить пользователя
                </a>
            </div>

            <!-- Вывод сообщений об ошибках и успехе -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="data-card">
                <div class="data-card__header">
                    <div class="data-card__info">
                        <h3>Все пользователи</h3>
                        <p>Управление учетными записями и ролями пользователей системы</p>
                    </div>
                    <div class="search-box">
                        <span class="material-symbols-outlined search-box__icon">search</span>
                        <input type="text" id="userSearch" class="search-box__input"
                            placeholder="Поиск пользователей по имени или email">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя пользователя</th>
                                <th>Email</th>
                                <th>Роль</th>
                                <th>Дата регистрации</th>
                                <th class="text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allUsers)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">Нет зарегистрированных пользователей.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allUsers as $user): ?>
                                    <tr class="user-row" data-search="<?= e(mb_strtolower($user['name'].' '.$user['email'])) ?>">
                                        <td class="text-muted">#<?= e($user['id']) ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <?php
                                                // Получаем первые буквы имени для аватара
                                                $initials = mb_substr($user['name'], 0, 1);
                                                // Если есть пробел, добавляем первую букву фамилии
                                                // Исправлено: strrchr возвращает строку от последнего пробела до конца, нужно взять её первую букву
                                                if (strrchr($user['name'], ' ')) {
                                                    $initials .= mb_substr((strrchr($user['name'], ' ')), 1, 1);
                                                }
                                                ?>

                                                <div class="avatar avatar--blue"><?= htmlspecialchars($initials) ?></div>
                                                <span class="font-medium"><?= htmlspecialchars($user['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <!-- Роль в системе (user['role']) -->
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge badge--red">Администратор</span>
                                            <?php elseif ($user['role'] === 'organizer'): ?>
                                                <span class="badge badge--blue">Организатор</span>
                                            <?php else: ?>
                                                <span class="badge badge--gray">Участник</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                        <td class="text-right">
                                            <a href="edit-user.php?id=<?= (int)$user['id'] ?>&role=<?= e($user['table_role']) ?>"
                                                class="action-btn" title="Редактировать"><span
                                                    class="material-symbols-outlined">edit</span></a>
                                            <form method="post" action="delete-user-handler.php" style="display:inline"
                                                onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                                <input type="hidden" name="role" value="<?= e($user['table_role']) ?>">
                                                <button type="submit" class="action-btn action-btn--danger" title="Удалить"><span class="material-symbols-outlined">delete</span></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-block">
                    <span class="pagination-info">Всего пользователей: <?= count($allUsers) ?></span>
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
    <script src="burger-menu.js"></script>
    <script>
        // Живой поиск пользователей по таблице
        const us = document.getElementById('userSearch');
        if (us) us.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.user-row').forEach(function (row) {
                row.style.display = row.dataset.search.includes(q) ? '' : 'none';
            });
        });
    </script>
</body>

</html>