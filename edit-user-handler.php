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

$id = (int) ($_POST['id'] ?? 0);
$role = $_POST['role'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$map = role_table($role);
if ($id <= 0 || $map === null) {
    $_SESSION['error'] = 'Некорректные данные.';
    header('Location: admin-page.php');
    exit;
}
$table = $map['table'];
$pk = $map['pk'];

if (empty($name) || empty($email)) {
    $_SESSION['error'] = 'Имя и email обязательны.';
    header("Location: edit-user.php?id=$id&role=$role");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Некорректный email.';
    header("Location: edit-user.php?id=$id&role=$role");
    exit;
}
if (!empty($password) && strlen($password) < 8) {
    $_SESSION['error'] = 'Пароль должен быть не менее 8 символов.';
    header("Location: edit-user.php?id=$id&role=$role");
    exit;
}

try {
    // Проверка дубликата email (исключая текущего пользователя)
    $stmt = $pdo->prepare("SELECT `$pk` FROM `$table` WHERE email = :email AND `$pk` != :id");
    $stmt->execute(['email' => $email, 'id' => $id]);
    if ($stmt->fetchColumn()) {
        $_SESSION['error'] = 'Пользователь с таким email уже существует.';
        header("Location: edit-user.php?id=$id&role=$role");
        exit;
    }

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE `$table` SET name = :name, email = :email, password = :password WHERE `$pk` = :id");
        $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hash, 'id' => $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE `$table` SET name = :name, email = :email WHERE `$pk` = :id");
        $stmt->execute(['name' => $name, 'email' => $email, 'id' => $id]);
    }

    $_SESSION['success'] = 'Данные пользователя обновлены!';
    header('Location: admin-page.php');
    exit;

} catch (PDOException $e) {
    error_log('edit-user: ' . $e->getMessage());
    $_SESSION['error'] = 'Не удалось обновить пользователя. Попробуйте позже.';
    header("Location: edit-user.php?id=$id&role=$role");
    exit;
}
