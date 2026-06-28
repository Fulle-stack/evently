<?php
session_start();
require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

// Доступ только авторизованным
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['user_role'] ?? '';
$map = role_table($role);
if ($map === null) {
    header('Location: Evently-hub.php');
    exit;
}
$table = $map['table'];
$pk = $map['pk'];
$uid = (int) $_SESSION['user_id'];

// Текущие данные пользователя
$stmt = $pdo->prepare("SELECT name, email, password FROM `$table` WHERE `$pk` = :id");
$stmt->execute(['id' => $uid]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$me) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '') {
        $error = 'Имя и email обязательны.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email.';
    } else {
        // email не должен совпадать с чужим в той же таблице
        $chk = $pdo->prepare("SELECT 1 FROM `$table` WHERE email = :email AND `$pk` != :id");
        $chk->execute(['email' => $email, 'id' => $uid]);
        if ($chk->fetchColumn()) {
            $error = 'Этот email уже используется другим пользователем.';
        }
    }

    // Смена пароля — только если заполнены поля нового пароля
    $changePassword = false;
    if ($error === '' && ($new !== '' || $confirm !== '' || $current !== '')) {
        if (!password_verify($current, $me['password'])) {
            $error = 'Текущий пароль указан неверно.';
        } elseif (strlen($new) < 8) {
            $error = 'Новый пароль должен быть не менее 8 символов.';
        } elseif ($new !== $confirm) {
            $error = 'Новый пароль и подтверждение не совпадают.';
        } else {
            $changePassword = true;
        }
    }

    if ($error === '') {
        try {
            if ($changePassword) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE `$table` SET name = :name, email = :email, password = :password WHERE `$pk` = :id");
                $upd->execute(['name' => $name, 'email' => $email, 'password' => $hash, 'id' => $uid]);
            } else {
                $upd = $pdo->prepare("UPDATE `$table` SET name = :name, email = :email WHERE `$pk` = :id");
                $upd->execute(['name' => $name, 'email' => $email, 'id' => $uid]);
            }
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $me['name'] = $name;
            $me['email'] = $email;
            $success = $changePassword ? 'Профиль и пароль обновлены.' : 'Профиль обновлён.';
        } catch (PDOException $e) {
            error_log('personal-account: ' . $e->getMessage());
            $error = 'Не удалось сохранить изменения. Попробуйте позже.';
        }
    }
}

$roleNames = ['admin' => 'Администратор', 'organizer' => 'Организатор', 'participant' => 'Участник'];
$initial = mb_strtoupper(mb_substr($me['name'], 0, 1));
$backLink = $role === 'organizer' ? 'my-event.php' : ($role === 'admin' ? 'admin-page.php' : 'event-search.php');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evently — Настройки профиля</title>
    <link rel="stylesheet" href="personal-account.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="alerts.css">
    <style>
        .avatar-letter{width:48px;height:48px;border-radius:50%;background:#0ea5e9;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:20px}
        .side-avatar.avatar-letter{width:64px;height:64px;font-size:26px}
        .alert-error,.alert-success{margin-bottom:16px}
    </style>
    <link rel="stylesheet" href="ui-polish.css">
</head>
<body>
    <header class="header">
        <div class="container header-content">
            <div class="header-left">
                <a href="Evently-hub.php" class="logo" style="text-decoration:none">
                    <span class="material-symbols-outlined logo-icon">confirmation_number</span>
                    <span class="logo-text">Evently</span>
                </a>
                <nav class="top-nav">
                    <a href="event-search.php">События</a>
                    <a href="my-tickets.php">Мои билеты</a>
                    <?php if ($role === 'organizer'): ?><a href="my-event.php">Мои события</a><?php endif; ?>
                </nav>
            </div>
            <div class="header-right">
                <div class="teg-name"><?= e($me['name']) ?></div>
                <div class="avatar-letter nav-avatar"><?= e($initial) ?></div>
            </div>
        </div>
    </header>

    <div class="container main-layout">
        <aside class="sidebar">
            <div class="lk-card">
                <div class="lk-user">
                    <div class="lk-ava"><?= e($initial) ?></div>
                    <div>
                        <div class="lk-name"><?= e($me['name']) ?></div>
                        <div class="lk-role"><?= e($roleNames[$role] ?? 'Пользователь') ?></div>
                    </div>
                </div>
                <nav class="lk-nav">
                    <a class="lk-link" href="my-tickets.php"><span class="material-symbols-outlined">confirmation_number</span> Мои билеты</a>
                    <?php if ($role === 'organizer'): ?>
                        <a class="lk-link" href="my-event.php"><span class="material-symbols-outlined">calendar_today</span> Мои события</a>
                    <?php else: ?>
                        <a class="lk-link" href="event-search.php"><span class="material-symbols-outlined">search</span> Найти событие</a>
                    <?php endif; ?>
                    <a class="lk-link is-active" href="personal-account.php"><span class="material-symbols-outlined">settings</span> Настройки профиля</a>
                    <div class="lk-sep"></div>
                    <a class="lk-link lk-link--danger" href="logout.php" onclick="return confirm('Вы уверены, что хотите выйти?')"><span class="material-symbols-outlined">logout</span> Выход</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <div class="form-card">
                <h1>Настройки профиля</h1>

                <?php if ($error): ?><div class="alert-error"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert-success"><?= e($success) ?></div><?php endif; ?>

                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="section">
                        <h2>Личные данные</h2>
                        <div class="grid-inputs">
                            <div class="field">
                                <label>Имя</label>
                                <input type="text" name="name" value="<?= e($me['name']) ?>" required>
                            </div>
                            <div class="field">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= e($me['email']) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2>Безопасность</h2>
                        <p style="color:#64748b;font-size:14px;margin-bottom:12px">Заполните, только если хотите сменить пароль.</p>
                        <div class="field">
                            <label>Текущий пароль</label>
                            <div class="pass-box">
                                <input type="password" name="current_password" placeholder="••••••••" autocomplete="current-password">
                            </div>
                        </div>
                        <div class="grid-inputs">
                            <div class="field">
                                <label>Новый пароль</label>
                                <input type="password" name="new_password" placeholder="не менее 8 символов" autocomplete="new-password">
                            </div>
                            <div class="field">
                                <label>Подтвердите новый пароль</label>
                                <input type="password" name="confirm_password" placeholder="••••••••" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">Сохранить изменения</button>
                </form>
            </div>
        </main>
    </div>

    <footer class="footer">
        <div class="container footer-content">
            <p>© <?= date('Y') ?> Evently. Все права защищены.</p>
            <div class="footer-links">
                <a href="data-processing-law.php">Политика конфиденциальности</a>
                <a href="<?= e($backLink) ?>">Назад</a>
            </div>
        </div>
    </footer>
</body>
</html>
