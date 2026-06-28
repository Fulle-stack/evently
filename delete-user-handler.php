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

$map = role_table($role);
if ($id <= 0 || $map === null) {
    $_SESSION['error'] = 'Некорректные параметры.';
    header('Location: admin-page.php');
    exit;
}
$table = $map['table'];
$pk = $map['pk'];

// Запрет удалять самого себя
if ($role === 'admin' && $id == $_SESSION['user_id']) {
    $_SESSION['error'] = 'Нельзя удалить самого себя!';
    header('Location: admin-page.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE `$pk` = :id");
    $stmt->execute(['id' => $id]);
    $_SESSION['success'] = 'Пользователь удалён!';
} catch (PDOException $e) {
    error_log('delete-user: ' . $e->getMessage());
    $_SESSION['error'] = 'Не удалось удалить пользователя.';
}

header('Location: admin-page.php');
exit;
