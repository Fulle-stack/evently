<?php

session_start();

require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

$name = $email = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password1 = $_POST['password1'] ?? '';
    $role = $_POST['role'] ?? 'participant';

    // Проверяем, что поля не пустые
    if (empty($email) || empty($password) || empty($name) || empty($password1)) {
        $error_message = 'Все поля обязательны для заполнерия!';
        // Хешируем пароль
    } elseif ($password !== $password1) {
        $error_message = 'Введенные пароли не совпадают!';
    } elseif (strlen($password) < 8) {
        $error_message = 'Пароль должен быть не менее 8 символов!';
    } elseif (!isset($_POST['personal_data_consent'])) {
        $error_message = 'Вы должны согласиться на обработку персональных данных!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Некорректный формат email!';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Регистрация в зависимости от роли
            if ($role === 'organizer') {
                // ПРОВЕРКА: есть ли организатор с таким email?
                $stmt = $pdo->prepare('SELECT id FROM organizers WHERE email = :email');
                // Говорим: "Вот конкретный email: ivan@mail.com. Найди!"
                $stmt->execute(['email' => $email]);
                // Ищем есть ли такая почта вообще. Если есть пишет отказ в регистрации и почта уже зарегана
                if ($stmt->rowCount() > 0) {
                    $error_message = 'Такой Email уже существует!';
                    // Не нашли! Можно регистрировать
                } else {
                    // Запрос на добавление нового организатора в БД
                    $stmt = $pdo->prepare("INSERT INTO organizers (name, email, password, role) VALUES (:name, :email, :password, :role)");
                    // Выполнение запроса с параметрами пользователя
                    $stmt->execute([
                        'name' => $name,    // Имя пользователя
                        'email' => $email,  // Email для входа
                        'password' => $password_hash, // Хеш пароля
                        'role' => 'organizer'  // роль пользователя
                    ]);
                    // ID только что созданной записи
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    // ячейка с ключом 'user_name'
                    $_SESSION['user_name'] = $name;
                    // ячейка с ключом 'user_role'
                    $_SESSION['user_role'] = 'organizer';

                    header('Location: login.php');
                    exit;
                }
            } else {
                // Регистрация участника
                // ПРОВЕРКА: есть ли участник с таким email?
                $stmt = $pdo->prepare("SELECT id_participants FROM participants WHERE email = :email");
                // Говорим: "Вот конкретный email: ivan@mail.com. Найди!"
                $stmt->execute(['email' => $email]);
                // Ищем есть ли такая почта вообще. Если есть пишет отказ в регистрации и почта уже зарегана
                if ($stmt->rowCount() > 0) {
                    // сообщение об ошибке
                    $error_message = 'Участник с таким email уже существует!';
                } else {
                    // Запрос на добавление нового участника в БД
                    $stmt = $pdo->prepare("INSERT INTO participants (name, email, password, role) VALUES (:name, :email, :password, :role)");

                    $stmt->execute([
                        'name' => $name,    // Имя пользователя
                        'email' => $email,  // Email для входа
                        'password' => $password_hash, // Хеш пароля
                        'role' => 'participant'  // роль пользователя
                    ]);

                    // ID только что созданной записи
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    // ячейка с ключом 'user_name'
                    $_SESSION['user_name'] = $name;
                    // ячейка с ключом 'user_role'
                    $_SESSION['user_role'] = 'participant';

                    header('Location: login.php');
                }
            }
            // Если запрос не удался — ловим ошибку здесь
        } catch (PDOException $e) {
            error_log('Registration DB error: ' . $e->getMessage());
            $error_message = 'Произошла ошибка. Попробуйте позже.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Evently - Регистрация</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="registration.css" />
    <link rel="stylesheet" href="ui-polish.css">
</head>

<body>
    <div class="page-wrapper">
        <header class="header">
            <div class="header-container">
                <div class="logo">
                    <div class="logo-icon-wrapper">
                        <span class="material-symbols-outlined">event</span>
                    </div>
                    <h2>Evently</h2>
                </div>
                <div class="header-actions">
                    <span class="header-text">Уже есть аккаунт?</span>
                    <a href="login.php" class="header-link">Войти</a>
                </div>
            </div>
        </header>

        <main class="main">
            <div class="form-container">
                <div class="form-header">
                    <h1>Регистрация</h1>
                    <p>Создайте учетную запись Evently, чтобы организовывать мероприятия и регистрироваться на них.</p>
                </div>
                <?php if (!empty($error_message)): ?>
                    <div
                        style="color: red; text-align: center; padding: 10px; background: white: border-radius: 5px; margin-bottom: 10px">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form class="register-form" action="" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="name">ФИО</label>
                        <div class="input-wrapper">
                            <input id="name" name="name" type="text" placeholder="Ваше имя"
                                value="<?php echo htmlspecialchars($name); ?>" required />
                            <span class="material-symbols-outlined input-icon">person</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="Почта"
                                value="<?php echo htmlspecialchars($email); ?>" required />
                            <span class="material-symbols-outlined input-icon">mail</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Пароль" required />

                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Подтвердите пароль</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm-password" name="password1" placeholder="Пароль"
                                required />

                        </div>
                    </div>

                    <div class="role-selector">
                        <span class="role-divider-text">выберите вашу роль</span>
                        <div class="role-options">
                            <label class="role-option">
                                <!-- Для участника: true когда НЕ выбрано ИЛИ выбрано participant -->
                                <input type="radio" name="role" value="participant" <?php echo (!isset($_POST['role']) || $_POST['role'] == 'participant') ? 'checked' : ''; ?>>
                                <span class="role-btn">Я участник</span>
                            </label>
                            <label class="role-option">
                                <!-- Для организатора: true только когда выбрано organizer -->
                                <input type="radio" name="role" value="organizer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'organizer') ? 'checked' : ''; ?>>
                                <span class="role-btn">Я организатор</span>
                            </label>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="personal_data_consent" id="personal_data_consent">
                        <label for="personal_data_consent">
                            Я даю согласие на обработку моих персональных данных в соответствии с
                            <a href="data-processing-law.php" target="_blank">Федеральным законом № 152-ФЗ</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary">Создать аккаунт</button>

                </form>
            </div>
        </main>

        <footer class="footer">
            <p>© 2026 Evently. Все права защищены.</p>
        </footer>
    </div>
</body>

</html>