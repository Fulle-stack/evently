<?php

session_start();


// Проверяем, что пользователь авторизован и он организатор или админ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['organizer', 'admin'])) {
    header('Location: Evently-hub.php');
    exit;
}

require __DIR__ . '/bd.php';

// Получаем id события и действие из URL
$event_id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

// Проверяем корректность параметров
if ($event_id <= 0 || !in_array($action, ['close', 'open'])) {
    $_SESSION['error'] = 'Некорректные параметры.';
    header('Location: my-event.php');
    exit;
}

// Определяем новый статус: close → 0, open → 1
$new_status = ($action === 'close') ? 0 : 1;

// Обновляем статус регистрации в базе
$stmt = $pdo->prepare("UPDATE event SET registration_open = :status WHERE id_event = :id");

$stmt->execute([
    'status' => $new_status,
    'id' => $event_id
]);

// Сообщение об успехе

if ($new_status === 1) {
    $_SESSION['success'] = 'Регистрация открыта!';
} else {
    $_SESSION['error'] = 'Регистрация закрыта!';
}

header('Location: my-event.php');
exit;

?>