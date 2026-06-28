<?php

session_start();

require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';


$success_message = ''; // Сюда потом положим текст об успешной отправке
$error_message = '';  // Сюда потом положим текст об ошибке

$stmt = $pdo->prepare("SELECT * FROM event WHERE status = 'active' ORDER BY start_date DESC LIMIT 3");

$stmt->execute();

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Статистика для блока доверия (реальные данные)
$statEvents = (int) $pdo->query("SELECT COUNT(*) FROM event WHERE status = 'active'")->fetchColumn();
$statCities = (int) $pdo->query("SELECT COUNT(DISTINCT city) FROM event WHERE status = 'active' AND city <> ''")->fetchColumn();
$statParticipants = (int) $pdo->query("SELECT COUNT(*) FROM participants")->fetchColumn();

$months_short = [
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

$category_names = [
    'education' => 'Образование',
    'business' => 'Бизнес',
    'sports' => 'Спорт',
    'music' => 'Музыка',
    'art' => 'Искусство',
    'texh' => 'Технологии',
    'entertainment' => 'Развлечение'
];


// Проверяем, была ли отправлена форма
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Получаем данные из формы
    $name = trim($_POST['name'] ?? '');  // Имя пользователя из поля name
    $phone = trim($_POST['phone'] ?? '');   // Телефон из поля phone
    $description = trim($_POST['message'] ?? ''); // Сообщение из поля message (сохраняем в description)

    // Валидация (проверка)
    // empty() проверяет - пустое ли значение
    if (empty($name) || empty($phone) || empty($description)) {
        // Сохраняем сообщение об ошибке в сессию
        $_SESSION['form_error'] = 'Все поля обязательны для заполнения!';
        // Сохраняем все данные, которые пользователь ввел, чтобы вернуть их в форму
        $_SESSION['form_data'] = $_POST;
        // Делаем редирект (перенаправление) на эту же страницу с якорем на форму
        header('Location: #messages');
        exit; // Останавливаем выполнение скрипта, дальше код не идет
        // Проверка 2: Достаточно ли длинное имя (минимум 3 символа)
    } elseif (strlen($name) < 3) {
        $_SESSION['form_error'] = 'Имя должно содержать минимум 3 символа';
        $_SESSION['form_data'] = $_POST;
        header('Location: #messages');
        exit;
    } elseif (strlen($description) < 6) {
        $_SESSION['form_error'] = 'Сообщение должно содержать минимум 6 символов';
        $_SESSION['form_data'] = $_POST;
        header('Location: #messages');
        exit;
        // Если все базовые проверки пройдены, идем дальше
    } else {
        // Очищаем телефон от маски (убираем всё кроме цифр и +)
        $clean_phone = preg_replace('/[^0-9+]/', '', $phone);

        // Проверка 4: Достаточно ли цифр в телефоне (минимум 10)
        if (strlen($clean_phone) < 12) {
            $_SESSION['form_error'] = 'Телефон должен содержать минимум 12 цифр';
            $_SESSION['form_data'] = $_POST;
            header('Location: #messages');
            exit;
            // Проверка 5: Не слишком ли длинный номер (максимум 15 цифр)
        } elseif (strlen($clean_phone) > 15) {
            $_SESSION['form_error'] = 'Номер слишком длинный';
            $_SESSION['form_data'] = $_POST;
            header('Location: #messages');
            // Если все проверки телефона пройдены, сохраняем в базу данных
        } else {
            try {
                // Подготовленный запрос для вставки в таблицу questions-users
                $stmt = $pdo->prepare('INSERT INTO questions (name, phone, description) VALUES (:name, :phone, :description)');

                // Выполняем запрос, подставляя реальные значения вместо :name, :phone, :description
                $stmt->execute([
                    'name' => $name,   // Имя пользователя
                    'phone' => $clean_phone, // Очищенный телефон
                    'description' => $description  // Текст сообщения
                ]);
                // Если дошли сюда - всё хорошо, данные сохранились!
                // Сохраняем сообщение об успехе в сессию
                $_SESSION['form_success'] = 'Спасибо! Ваше сообщение отправлено. Мы скоро с вами свяжемся!';

                // Очищаем данные формы при успехе (чтобы поля стали пустыми)
                // unset() удаляет переменную
                unset($_SESSION['form_data']);
                // Делаем редирект на эту же страницу (чтобы при обновлении форма не отправилась снова)
                header('Location: #messages');
                exit;
                // Если произошла ошибка при работе с базой данных, попадаем сюда
                // PDOException - исключение (ошибка) базы данных
            } catch (PDOException $e) {
                // Ошибка базы данных
                error_log('Contact form DB error: ' . $e->getMessage());
                $_SESSION['form_error'] = 'Произошла ошибка при сохранении. Попробуйте позже.';
                // Сохраняем данные формы, чтобы пользователь не вводил их заново
                $_SESSION['form_data'] = $_POST;
                // Делаем редирект на эту же страницу
                header('Location: #messages');
                exit; // Останавливаем скрипт
            }
        }
    }
}

// ЭТОТ КОД ВЫПОЛНЯЕТСЯ ТОЛЬКО ПРИ GET ЗАПРОСЕ (когда просто зашли на страницу)
// ИЛИ ПОСЛЕ РЕДИРЕКТА

// Получаем сообщения из сессии
// ?? '' означает: если в сессии нет такого ключа, используем пустую строку
$success_message = $_SESSION['form_success'] ?? '';  // Достаем сообщение об успехе
$error_message = $_SESSION['form_error'] ?? '';  // Достаем сообщение об ошибке
$form_data = $_SESSION['form_data'] ?? '';  // Достаем сохраненные данные формы (если есть)

// Очищаем сессию (выбрасываем использованные сообщения)
// Это нужно, чтобы при обновлении страницы сообщения не появлялись снова
unset($_SESSION['form_success']); // Удаляем сообщение об успехе из сессии
unset($_SESSION['form_error']); // Удаляем сообщение об ошибке из сессии
unset($_SESSION['form_data']);  // Удаляем данные формы из сессии

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Evently - Организуйте мероприятия легко</title>

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="Evently-style.css">
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
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin')): ?>
                    <a href="my-event.php">Мои события</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin-event.php">Модерация</a>
                    <a href="admin-page.php">Пользователи</a>
                <?php endif; ?>
                <a href="data-processing-law.php">Помощь</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="link-logout" onclick="return confirm('Вы уверены, что хотите выйти?')">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="registration.php">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <section class="hero-section">
            <div class="container">
                <div class="hero-card">
                    <div class="hero-bg"
                        style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDdSNZJ0KYsHBrQO2Xs8bfmJcfMBfWLBWDBKEkeriqG_AydB73XGIkx8xliNV2qGVlumjJxNrdkSmX9oEK4E4e5ZXUq_xWlTVtIaeSZptczdvFQrQoQPhurnyVwKhsPwUYS1Fznv8ETkLjBThtYNP7MFX08mYGbQvWMRRHx-0s3BxvaQzCMkNm6_g-YD7cH23drUPphV6X2stTd128R8ApwUH32tGNfqvRisl1Il1CbA9T2QlY1AtpEEuhvz6mS7duUWwFDAtIV0b8");'>
                    </div>
                    <div class="hero-overlay"></div>
                    <div class="hero-content">
                        <span class="badge badge-primary">Платформа для мероприятий</span>
                        <h1>Организуйте мероприятия легко</h1>
                        <p>Создавайте, управляйте и находите события в несколько кликов. Идеальное решение для
                            конференций, воркшопов и встреч.</p>
                        <div class="hero-buttons">
                            <?php if (($_SESSION['user_role'] ?? '') === 'organizer'): ?>
                                <a class="btn btn-primary" href="forma-event.php">
                                    <span class="material-symbols-outlined">add_circle</span>
                                    Создать событие
                                </a>
                            <?php elseif (isset($_SESSION['user_id'])): ?>
                                <a class="btn btn-primary" href="event-search.php">
                                    <span class="material-symbols-outlined">search</span>
                                    Найти событие
                                </a>
                            <?php else: ?>
                                <a class="btn btn-primary" href="registration.php">
                                    <span class="material-symbols-outlined">add_circle</span>
                                    Создать событие
                                </a>
                            <?php endif; ?>
                            <a class="btn btn-outline" href="event-search.php">Узнать больше</a>
                        </div>
                        <form class="hero-search" action="event-search.php" method="GET" role="search">
                            <span class="material-symbols-outlined">search</span>
                            <input type="text" name="search" placeholder="Поиск мероприятий по названию…" aria-label="Поиск мероприятий">
                            <button type="submit">Найти</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Полоса статистики (доверие) -->
        <section class="stats-strip">
            <div class="container">
                <div class="stats-strip__grid">
                    <div class="stat-item">
                        <span class="stat-item__num"><?= $statEvents ?></span>
                        <span class="stat-item__label">активных мероприятий</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__num"><?= $statCities ?></span>
                        <span class="stat-item__label">городов</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__num"><?= $statParticipants ?></span>
                        <span class="stat-item__label">участников</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__num">0%</span>
                        <span class="stat-item__label">комиссия для участника</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="events-section">
            <div class="container">
                <div class="section-header">
                    <div class="section-title">
                        <h2>Ближайшие события</h2>
                        <p>Выберите интересное мероприятие и присоединяйтесь</p>
                    </div>
                    <a href="event-search.php" class="view-all">
                        Смотреть все
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </a>
                </div>
                   
                <div class="events-grid">
                    <?php if (empty($events)): ?>
                    <p class="no-events">Пока нет активных событий.</p>
                <?php else: ?>
                     <?php foreach ($events as $event): ?>
                    <a class="event-card event-card--link" href="event-page.php?id=<?= (int)$event['id_event'] ?>">
                        <div class="card-image-wrap">
                            <div class="date-badge"><?= date('d', strtotime($event['start_date'])) ?> <?= $months_short[date('M', strtotime($event['start_date']))] ?></div>
                            <img class="card-image" src="<?= htmlspecialchars($event['photo']) ?>" alt="<?= htmlspecialchars($event['title']) ?>" />
                            <div class="card-overlay"></div>
                        </div>
                        <div class="card-body">
                            <div class="card-category">
                                <span class="tag tag-tech"><?= htmlspecialchars($category_names[$event['category']] ?? $event['category']) ?></span>
                            </div>
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
                            <div class="event-location">
                                <span class="material-symbols-outlined">location_on</span>
                                <?= htmlspecialchars($event['city']) ?>, <?= htmlspecialchars($event['venue_name']) ?>
                            </div>
                            <span class="btn btn-primary btn-full mt-auto">Подробнее</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="features-section">
            <div class="container">
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <span class="material-symbols-outlined">rocket_launch</span>
                        </div>
                        <h3>Быстрый старт</h3>
                        <p>Создайте страницу мероприятия за 5 минут без навыков программирования.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <span class="material-symbols-outlined">group</span>
                        </div>
                        <h3>Управление гостями</h3>
                        <p>Удобные списки, билеты и аналитика посещаемости в одном месте.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <h3>Продажа билетов</h3>
                        <p>Принимайте оплату онлайн безопасно и с низкой комиссией.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <section class="contact-section">
        <div class="container">
            <div id="messages"></div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="contact-wrapper">

                <div class="contact-text">
                    <h2 class="section-title">Остались вопросы?</h2>
                    <p class="section-subtitle">Напишите нам, и мы с радостью поможем вам с организацией вашего
                        следующего события.</p>
                </div>

                <form class="contact-form" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Ваше имя" required
                            value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" class="form-input">
                    </div>

                    <div class="form-group">
                        <input type="tel" id="phone" name="phone" placeholder="+7 (___) ___-__-__" required
                            value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" class="form-input">
                    </div>

                    <div class="form-group">
                        <textarea name="message" placeholder="Ваше сообщение" rows="5" required
                            class="form-input"><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Отправить сообщение</button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo">
                        <span class="material-symbols-outlined">event_available</span>
                        <h2>Evently</h2>
                    </div>
                    <p>Ваш надежный партнер в мире событий. Организуйте, посещайте, вдохновляйтесь.</p>
                </div>
                <div class="footer-links">
                    <h4>Платформа</h4>
                    <ul>
                        <li><a href="event-search.php">Все события</a></li>
                        <li><a href="forma-event.php">Создать событие</a></li>
                        <li><a href="Evently-hub.php">Тарифы</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Поддержка</h4>
                    <ul>
                        <li><a href="data-processing-law.php">Справка</a></li>
                        <li><a href="data-processing-law.php">Контакты</a></li>
                        <li><a href="data-processing-law.php">Правила</a></li>
                    </ul>
                </div>
                <div class="footer-subscribe">
                    <h4>Подписаться</h4>
                    <form>
                        <input type="email" placeholder="Email" />
                        <button type="submit">
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2026 Evently. Все права защищены.</p>
                <div class="legal-links">
                    <a href="data-processing-law.php">Политика конфиденциальности</a>
                    <a href="data-processing-law.php">Условия использования</a>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://unpkg.com/imask"></script>
    <script src="burger-menu.js"></script>
    <script src="number_mask.js"></script>
</body>

</html>