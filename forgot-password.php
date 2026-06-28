<?php
session_start();
require __DIR__ . '/helpers.php';

$message = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Введите корректный адрес электронной почты.';
    } else {
        // Из соображений безопасности не раскрываем, существует ли аккаунт.
        // Отправка письма со ссылкой сброса — на стороне почтового сервиса (планируется к подключению).
        $sent = true;
        $message = 'Если аккаунт с таким email существует, на него отправлены инструкции по сбросу пароля.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evently — Восстановление пароля</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="forgot-password.css">
    <style>
        .fp-message{margin:14px 0;padding:12px 14px;border-radius:10px;font-size:14px;line-height:1.4}
        .fp-message--ok{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
        .fp-message--err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
    </style>
    <link rel="stylesheet" href="ui-polish.css">
</head>
<body>
    <header class="header">
        <div class="header__left">
            <a href="Evently-hub.php" class="logo">
                <span class="logo__icon material-symbols-outlined">event_available</span>
                <span class="logo__text">Evently</span>
            </a>
        </div>
        <div class="header__right">
            <div class="tag-name">Уже есть аккаунт?</div>
            <div class="user-profile">
                <a href="login.php" class="btn-login">Войти</a>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="frame__forgot">
                <div class="block__description">
                    <div class="title">Восстановление пароля</div>
                    <p class="text">Введите адрес электронной почты, связанный с вашим аккаунтом, и мы отправим вам инструкции по сбросу пароля.</p>
                </div>

                <?php if ($message): ?>
                    <div class="fp-message <?= $sent ? 'fp-message--ok' : 'fp-message--err' ?>"><?= e($message) ?></div>
                <?php endif; ?>

                <?php if (!$sent): ?>
                <form class="forgot-form" method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="input-group">
                        <label for="email" class="input-label">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" class="input-field" placeholder="example@email.com" required>
                            <span class="material-symbols-outlined input-icon">mail</span>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Отправить инструкции</button>
                </form>
                <?php endif; ?>

                <div class="back-link-wrapper">
                    <a href="login.php" class="back-link">
                        <span class="material-symbols-outlined back-icon">arrow_back</span>
                        Вернуться к входу
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
