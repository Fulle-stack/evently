<?php

session_start();

require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

// Только администратор
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: Evently-hub.php');
    exit;
}
csrf_check();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'participant';
$phone = trim($_POST['phone'] ?? '');
$surname = trim($_POST['surname'] ?? '');

if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['error'] = 'Все поля обязательны для заполнения.';
    header('Location: add-user.php');
    exit;
}
if (strlen($password) < 8) {
    $_SESSION['error'] = 'Пароль должен быть не менее 8 символов.';
    header('Location: add-user.php');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Некорректный email.';
    header('Location: add-user.php');
    exit;
}

$map = role_table($role);
if ($map === null) {
    $_SESSION['error'] = 'Некорректная роль.';
    header('Location: add-user.php');
    exit;
}
$table = $map['table'];

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Проверяем, нет ли уже такого email в выбранной таблице
    $stmt = $pdo->prepare("SELECT 1 FROM `$table` WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn()) {
        $_SESSION['error'] = 'Пользователь с таким email уже существует.';
        header('Location: add-user.php');
        exit;
    }

    // Состав колонок зависит от таблицы: phone — у participants и organizers (NOT NULL),
    // surname — только у participants (NOT NULL).
    $params = ['name' => $name, 'email' => $email, 'password' => $password_hash, 'role' => $role];
    if ($table === 'participants') {
        $params['phone'] = $phone;
        $params['surname'] = $surname;
    } elseif ($table === 'organizers') {
        $params['phone'] = $phone;
    }
    $cols = implode(', ', array_keys($params));
    $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($params)));
    $stmt = $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($ph)");
    $stmt->execute($params);

    $_SESSION['success'] = 'Пользователь успешно добавлен.';
    header('Location: admin-page.php');
    exit;

} catch (PDOException $e) {
    error_log('add-user: ' . $e->getMessage());
    $_SESSION['error'] = 'Не удалось добавить пользователя. Попробуйте позже.';
    header('Location: add-user.php');
    exit;
}
