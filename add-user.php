<?php

session_start();

// Проверяем, авторизован ли пользователь и является ли он администратором
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Если нет, перенаправляем на страницу входа или главную страницу
    header('Location: Evently-hub.php');
    exit;
}

require __DIR__ . '/helpers.php';

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить нового пользователя - Evently</title>
    <link rel="stylesheet" href="add-user.css">
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
                <a href="event-search.php" class="nav__link">События</a>
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
                <a href="add-user.php" class="sidebar__link active">
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
                <span>Добавить пользователя</span>
            </div>

            <div class="page-header">
                <h1 class="page-title">Добавить нового пользователя</h1>
                <p class="page-subtitle">Создайте новую учетную запись для доступа к платформе Evently.</p>
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
                <form class="form" action="add-user-handler.php" method="POST">
                    <?= csrf_field() ?>
                    <div class="form__grid">
                        <div class="form__section">
                            <h3 class="form__section-title">
                                <span class="material-symbols-outlined">person</span>
                                Личные данные
                            </h3>

                            <div class="form__group">
                                <label for="fullName">Полное имя</label>
                                <input type="text" id="fullName" name="name" placeholder="Иван Иванов" required>
                            </div>

                            <div class="form__group">
                                <label for="email">Email адрес</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">mail</span>
                                    <input type="email" id="email" name="email" placeholder="ivan@example.com" required>
                                </div>
                            </div>

                            <div class="form__group">
                                <label for="password">Пароль</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">lock</span>
                                    <input type="password" id="password" name="password" placeholder="••••••••"
                                        required>
                                </div>
                                <span class="form__hint">Минимум 8 символов, включая цифры.</span>
                            </div>

                            <div class="form__group">
                                <label for="phone">Телефон</label>
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">call</span>
                                    <input type="tel" id="phone" name="phone" placeholder="+7 900 000-00-00">
                                </div>
                            </div>

                            <div class="form__group">
                                <label for="surname">Фамилия <span class="form__hint">(для участника)</span></label>
                                <input type="text" id="surname" name="surname" placeholder="Иванов">
                            </div>
                        </div>

                        <div class="form__section">
                            <h3 class="form__section-title">
                                <span class="material-symbols-outlined">admin_panel_settings</span>
                                Права доступа
                            </h3>

                            <label class="form__label">Роль в системе</label>
                            <div class="role-options">
                                <label class="role-card">
                                    <input type="radio" name="role" value="admin">
                                    <div class="role-card__content">
                                        <div class="role-card__icon role-card__icon--admin">
                                            <span class="material-symbols-outlined"
                                                style="font-variation-settings: 'FILL' 1;">shield_person</span>
                                        </div>
                                        <div class="role-card__text">
                                            <h4>Администратор</h4>
                                            <p>Полный доступ ко всем функциям.</p>
                                        </div>
                                        <div class="role-card__radio"></div>
                                    </div>
                                </label>

                                <label class="role-card">
                                    <input type="radio" name="role" value="organizer">
                                    <div class="role-card__content">
                                        <div class="role-card__icon role-card__icon--organizer">
                                            <span class="material-symbols-outlined"
                                                style="font-variation-settings: 'FILL' 1;">event</span>
                                        </div>
                                        <div class="role-card__text">
                                            <h4>Организатор</h4>
                                            <p>Создание и управление событиями.</p>
                                        </div>
                                        <div class="role-card__radio"></div>
                                    </div>
                                </label>

                                <label class="role-card">
                                    <input type="radio" name="role" value="participant" checked>
                                    <div class="role-card__content">
                                        <div class="role-card__icon role-card__icon--participant">
                                            <span class="material-symbols-outlined"
                                                style="font-variation-settings: 'FILL' 1;">person</span>
                                        </div>
                                        <div class="role-card__text">
                                            <h4>Участник</h4>
                                            <p>Просмотр и регистрация на события.</p>
                                        </div>
                                        <div class="role-card__radio"></div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form__actions">
                        <button type="button" class="btn-cancel">Отмена</button>
                        <button type="submit" class="btn-submit">
                            <span class="material-symbols-outlined">person_add</span>
                            Добавить пользователя
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>