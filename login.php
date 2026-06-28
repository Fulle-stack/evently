<?php

session_start();
require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    if (empty($email) || empty($password)) {
        $error_message = 'Заполните все поля!';
    } else {
        try {
            // === ПОИСК ПОЛЬЗОВАТЕЛЯ В ДВУХ ТАБЛИЦАХ ===
            // Пользователь может быть либо организатором, либо участником
            // Сначала проверяем таблицу organizers

            // СНАЧАЛА ищем в таблице organizers
            $stmt = $pdo->prepare('SELECT id, name, password, role FROM organizers WHERE email = :email');
            // Выполняем подготовленный запрос, заменяя :email на реальный email из формы
            $stmt->execute(['email' => $email]);
            //Верни как ассоциативный массив - только по именам:
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Если в organizers не нашли ($user = false), ищем в participants
            if (!$user) {
                // SELECT "ВЫБЕРИ" - начало запроса на выборку id, password, role - Какие колонки хотим получить
                // FROM	"ИЗ" - указывает откуда брать Из таблицы organizers "ГДЕ" - условие отбора Колонка email в таблице "РАВНО" - сравнивает
                // :email	Плейсхолдер	"Место для подстановки email"
                $stmt = $pdo->prepare('SELECT id_participants as id, name, password, role FROM participants WHERE email = :email');
                // Выполняем подготовленный запрос, заменяя :email на реальный email из формы
                $stmt->execute(['email' => $email]);
                //Верни как ассоциативный массив - только по именам:
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Если не нашли в organizers и participants, ищем в admins
            if (!$user) {
                // SELECT "ВЫБЕРИ" - начало запроса на выборку id, password, role - Какие колонки хотим получить
                // FROM	"ИЗ" - указывает откуда брать Из таблицы organizers "ГДЕ" - условие отбора Колонка email в таблице "РАВНО" - сравнивает
                // :email	Плейсхолдер	"Место для подстановки email"
                $stmt = $pdo->prepare('SELECT id, name, password, role FROM admins WHERE email = :email');

                // Выполняем подготовленный запрос, заменяя :email на реальный email из формы
                $stmt->execute(['email' => $email]);

                //Верни как ассоциативный массив - только по именам:
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Проверяем, нашли ли пользователя и совпадает ли пароль
            if ($user && password_verify($password, $user['password'])) {
                // Успешный вход!
                // Теперь у всех есть поле 'id' благодаря 'as id' в запросе
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $email;
                $_SESSION['user_role'] = $user['role']; // Использую user_role для единообразия
                $_SESSION['name'] = $user['name'];
                // Перенаправление в зависимости от роли
                if ($user['role'] === 'organizer') {
                    header('Location: Evently-hub.php');
                } else {
                    header('Location: Evently-hub.php');
                }
                exit;
            } else {
                $error_message = 'Неверный email или пароль!';
            }
        } catch (PDOException $e) {
            error_log('login: ' . $e->getMessage());
            $error_message = 'Ошибка базы данных. Попробуйте позже.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Evently - Вход в систему</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="login.css" />
    <link rel="stylesheet" href="ui-polish.css">
</head>

<body>
   <header class="header">
    <div class="header-container">
        <div class="logo">
            <span class="material-symbols-outlined logo-icon">event_available</span>
            <h2>Evently</h2>
        </div>
        <a href="registration.php" class="header-link">Регистрация</a>
    </div>
</header>
    <main class="main">
        <div class="card">
            <?php if (!empty($error_message)): ?>
                <div
                    style="color: red; text-align: center; padding: 10px; background: white: border-radius: 5px; margin-bottom: 10px">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <div class="card-header">
                <h1>Вход в систему</h1>
                <p>Введите свои данные для доступа к аккаунту</p>
            </div>

            <form class="form" method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <input id="email" type="email" name="email" placeholder="example@email.com" />
                        <span class="material-symbols-outlined input-icon">mail</span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="password-header">
                        <label for="password">Пароль</label>
                        <a href="forgot-password.php" class="forgot-link">Забыли пароль?</a>
                    </div>
                    <div class="input-wrapper">
                        <input id="password" type="password" name="password" placeholder="••••••••" />
                    </div>
                </div>

                <button type="submit" class="btn-submit">Войти</button>

                <div class="divider">
                    <span>или</span>
                </div>

                <div class="register-prompt">
                    <p>Ещё не зарегистрированы?<a href="registration.php"> Регистрация </a></p>
                </div>
            </form>

            <!-- Демо-доступы для защиты (клик — автозаполнение) -->
            <div class="demo-creds">
                <div class="demo-creds__title">
                    <span class="material-symbols-outlined">vpn_key</span>
                    Демо-доступы (пароль у всех: <b>demo123</b>)
                </div>
                <button type="button" class="demo-creds__row" data-email="kivevid147@anawebs.com">
                    <span class="demo-creds__role">Администратор</span>
                    <span class="demo-creds__email">kivevid147@anawebs.com</span>
                </button>
                <button type="button" class="demo-creds__row" data-email="nowag@koletter.com">
                    <span class="demo-creds__role">Организатор</span>
                    <span class="demo-creds__email">nowag@koletter.com</span>
                </button>
                <button type="button" class="demo-creds__row" data-email="dhdhdhgdueh@gmail.com">
                    <span class="demo-creds__role">Участник</span>
                    <span class="demo-creds__email">dhdhdhgdueh@gmail.com</span>
                </button>
                <p class="demo-creds__hint">Нажмите на строку — поля заполнятся автоматически.</p>
            </div>
        </div>
    </main>

    <style>
        .demo-creds{margin-top:1.25rem;padding:1rem;border:1px dashed #cbd5e1;border-radius:.75rem;background:#f8fafc}
        .demo-creds__title{display:flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:600;color:#334155;margin-bottom:.6rem}
        .demo-creds__title .material-symbols-outlined{font-size:18px;color:#0ea5e9}
        .demo-creds__row{display:flex;justify-content:space-between;align-items:center;gap:.75rem;width:100%;
            background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.55rem .75rem;margin-bottom:.4rem;
            cursor:pointer;font-family:inherit;text-align:left;transition:all .15s ease}
        .demo-creds__row:hover{border-color:#0ea5e9;box-shadow:0 2px 8px rgba(14,165,233,.12)}
        .demo-creds__role{font-size:.85rem;font-weight:600;color:#0f172a;white-space:nowrap}
        .demo-creds__email{font-size:.78rem;color:#64748b;font-family:monospace;overflow:hidden;text-overflow:ellipsis}
        .demo-creds__hint{font-size:.72rem;color:#94a3b8;margin:.4rem 0 0}
    </style>
    <script>
        document.querySelectorAll('.demo-creds__row').forEach(function (row) {
            row.addEventListener('click', function () {
                document.getElementById('email').value = this.dataset.email;
                document.getElementById('password').value = 'demo123';
                document.getElementById('password').focus();
            });
        });
    </script>

    <footer class="footer">
        © 2026 Evently. Все права защищены.
    </footer>
</body>

</html>