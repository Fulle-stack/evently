<?php

// 1. ЗАПУСК СЕССИИ
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: Evently-hub.php');
}

// ПОЛУЧАЕМ ВЫБРАННЫЕ МЕСТА ИЗ ФОРМЫ
// превращает JSON из формы обратно в массив PHP
$selected_seats = json_decode($_POST['selected_seats'] ?? '[]', true);

// ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

csrf_check();

// ПОЛУЧАЕМ ДАННЫЕ ИЗ ФОРМЫ
$event_id = $_POST['event_id']; // ПОЛУЧАЕМ ДАННЫЕ ИЗ ФОРМЫ

$user_id = $_SESSION['user_id']; //  ПОЛУЧАЕМ ID ПОЛЬЗОВАТЕЛЯ ИЗ СЕССИИ

//  ПРОВЕРКА: ЗАЛОГИНЕН ЛИ ПОЛЬЗОВАТЕЛЬ?
// isset() - проверяет, существует ли переменная
if (!isset($_SESSION['user_id'])) {
    header('Location: Evently-hub.php');
    exit; // Останавливаем выполнение скрипта
}

// ПРОВЕРКА: ОРГАНИЗАТОР НЕ МОЖЕТ ПОКУПАТЬ
if ($_SESSION['user_role'] === 'organizer') {
    $_SESSION['error'] = "Организаторы не могут покупать билеты!";
    header("Location: event-page.php?id=$event_id");
    exit;
}

// Проверка - существует ли такое мероприятие? (получаем цену заодно)
$stmt = $pdo->prepare("SELECT id_event, price FROM event WHERE id_event = :id_event");

// Выполнение запроса с подстановкой значения
$stmt->execute(['id_event' => $event_id]);
$event = $stmt->fetch();

// Если событие НЕ найдено ($event = false)
if (!$event) {
    // Сохраняем ошибку в сессию
    $_SESSION['error'] = "Мероприятие не найдено!";
    // Отправляем пользователя на страницу поиска событий
    header('Location: event-search.php');
    exit;
}

// ПРОВЕРКА: ЕСТЬ ЛИ ВЫБРАННЫЕ МЕСТА
// $selected_seats — массив выбранных мест из формы
// empty() проверяет, пустой ли массив
if (empty($selected_seats)) {
    // Если массив пустой — значит места не выбраны
    $_SESSION['error'] = 'Выберите места!';
    // Возвращаем на страницу события
    header("Location: event-page.php?id=$event_id");
    exit;
}

// ПРОВЕРКА: НЕ БОЛЬШЕ 5 БИЛЕТОВ
if (count($selected_seats) > 5) {
    $_SESSION['error'] = 'За один раз можно заказать не более 5 билетов';
    header("Location: event-page.php?id=$event_id");
    exit;
}

try {
    // 1. НАЧИНАЕМ ВРЕМЕННОЕ СОХРАНЕНИЕ
    // Все следующие изменения в БД будут временными
    $pdo->beginTransaction();

    // пустой массив для ID билетов
    $ticket_ids = [];
    // общая стоимость всех выбранных билетов
    $total_amount = 0;

    foreach ($selected_seats as $seat) {
        // 1. Проверяем статус места и БЛОКИРУЕМ его (чтобы два человека не купили одновременно)
        $checkStmt = $pdo->prepare("SELECT status FROM seats WHERE id_seat = :id FOR UPDATE");

        // 2. Выполняем запрос для конкретного места (подставляем ID)
        $checkStmt->execute(['id' => $seat['id']]);

        // 3. Забираем результат (['status' => 'free'] или false)
        $seatStatus = $checkStmt->fetch();

        // ВНИМАНИЕ! throw new = ЭКСТРЕННАЯ ОСТАНОВКА
        // - КОД ДАЛЬШЕ НЕ ВЫПОЛНЯЕТСЯ!
        // - СРАЗУ ПЕРЕХОДИТ В catch
        // - ТЕКСТ УЛЕТАЕТ В $e->getMessage()
        if (!$seatStatus || $seatStatus['status'] !== 'free') {
            throw new Exception("Место {$seat['row']}{$seat['num']} уже занято!");
        }

        // БРОНИРУЕМ МЕСТО
        $seatStmt = $pdo->prepare("UPDATE seats SET status = 'sold' WHERE id_seat = :id");
        $seatStmt->execute(['id' => $seat['id']]);

        // 1. Генерируем уникальный номер билета
        $ticket_number = 'TK-' . time() . '-' . random_int(1000, 9999);

        // 2. Готовим запрос на добавление билета
        $stmt = $pdo->prepare("INSERT INTO tickets (ticket_number, event_id, seat_id, price_purchase, status) VALUES (:ticket_number, :event_id, :seat_id, :price, 'active')");

        // 3. Выполняем запрос с конкретными значениями
        $stmt->execute([
            'ticket_number' => $ticket_number, // уникальный номер билета
            'event_id' => $event_id, // ID события (концерт)
            'seat_id' => $seat['id'], // ID места (ряд А, место 5)
            'price' => $seat['price']
        ]);

        // Добавляем ID созданного билета в массив (потом свяжем с платежом)
        $ticket_ids[] = $pdo->lastInsertId();

        // Прибавляем цену билета к общей сумме покупки
        $total_amount += $seat['price'];
    }


    // Вставляем платеж в таблицу payments
    // Поле id_tickets НЕ заполняем, так как билетов может быть несколько
    // Связь билетов с платежом сделаем ниже через UPDATE
    $stmt = $pdo->prepare("INSERT INTO payments (amount, status, id_event) VALUES (:amount, 'pending', :id_event)"); // Результат: id_payment = 5 (база дала ID)
    // ВЫПОЛНЕНИЕ (заполняем пустые места и отправляем в БД)
    $stmt->execute([
        'amount' => $total_amount,
        'id_event' => $event_id,
    ]);

    // Запоминаем ID созданного платежа
    $payment_id = $pdo->lastInsertId();

    // каждый будет связан с этим платежом через id_payment
    // Связываем каждый билет с платежом
    foreach ($ticket_ids as $ticket_id) {

        // Готовим запрос: обновить билет, добавить в него ID платежа
        $stmt = $pdo->prepare("UPDATE tickets SET id_payment = :id_payment WHERE id_tickets = :id_tickets");

        // Выполняем запрос с конкретными значениями
        $stmt->execute([
            'id_payment' => $payment_id, // один платеж
            'id_tickets' => $ticket_id // каждый билет
        ]);
    }

    // 2. Создаем запись в reservation с полученным payment_id
    $stmt = $pdo->prepare("INSERT INTO reservation (id_participants, id_event, id_payment) VALUES (:id_participants, :id_event, :id_payment)");
    $stmt->execute([
        'id_participants' => $user_id,
        'id_event' => $event_id,
        'id_payment' => $payment_id
    ]);

    // 2. ВСЁ ХОРОШО - ПОДТВЕРЖДАЕМ
    // Временные изменения становятся постоянными
    $pdo->commit();
    $_SESSION['success'] = "✅ Успешно куплено " . count($selected_seats) . " билет(ов) на сумму $total_amount ₽. Посмотреть билеты можно в разделе «Мои билеты».";
    header("Location: event-page.php?id=$event_id");

    // 3. ОТКАТЫВАЕМ временные изменения
    // Места снова становятся свободными
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Buy ticket error: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: event-page.php?id=$event_id");
}

?>ы