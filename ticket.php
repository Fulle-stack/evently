<?php
session_start();
require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$uid = (int) $_SESSION['user_id'];
$ticketId = (int) ($_GET['id'] ?? 0);

// Билет + проверка, что он принадлежит текущему участнику
$stmt = $pdo->prepare("
    SELECT t.id_tickets, t.ticket_number, t.price_purchase, t.status,
           e.title, e.start_date, e.start_time, e.venue_name, e.city, e.category,
           s.seat_row, s.seat_number
    FROM tickets t
    JOIN event e ON t.event_id = e.id_event
    JOIN reservation r ON t.id_payment = r.id_payment
    LEFT JOIN seats s ON t.seat_id = s.id_seat
    WHERE t.id_tickets = :tid AND r.id_participants = :uid
");
$stmt->execute(['tid' => $ticketId, 'uid' => $uid]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    http_response_code(404);
    $notFound = true;
}

$category_names = [
    'education' => 'Образование', 'business' => 'Бизнес', 'sports' => 'Спорт',
    'music' => 'Музыка', 'art' => 'Искусство', 'texh' => 'Технологии',
    'tech' => 'Технологии', 'entertainment' => 'Развлечение',
];
$months_ru = [1=>'января',2=>'февраля',3=>'марта',4=>'апреля',5=>'мая',6=>'июня',7=>'июля',8=>'августа',9=>'сентября',10=>'октября',11=>'ноября',12=>'декабря'];

if (empty($notFound)) {
    $ts = strtotime($ticket['start_date']);
    $dateStr = (int)date('j', $ts) . ' ' . $months_ru[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    $seatStr = $ticket['seat_row'] ? ('Ряд ' . $ticket['seat_row'] . ', место ' . $ticket['seat_number']) : 'Без места';
    // QR кодирует номер билета (для проверки на входе)
    $qrData = urlencode('EVENTLY-TICKET:' . $ticket['ticket_number']);
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=$qrData";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evently — Ваш билет</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;color:#0f172a;min-height:100vh}
        .topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:16px 24px;display:flex;align-items:center;justify-content:space-between}
        .logo{display:flex;align-items:center;gap:8px;color:#0ea5e9;font-weight:800;font-size:20px;text-decoration:none}
        .back-link{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:14px}
        .back-link:hover{color:#0ea5e9}
        .wrap{max-width:560px;margin:40px auto;padding:0 16px}
        .ticket{background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(15,23,42,.08)}
        .ticket__top{background:linear-gradient(135deg,#0ea5e9,#2563eb);color:#fff;padding:28px 28px 22px}
        .ticket__cat{display:inline-block;background:rgba(255,255,255,.2);border-radius:999px;padding:4px 12px;font-size:12px;font-weight:600;margin-bottom:10px}
        .ticket__title{font-size:24px;font-weight:800;line-height:1.2}
        .ticket__body{padding:24px 28px;display:flex;gap:24px;flex-wrap:wrap;align-items:center}
        .ticket__info{flex:1;min-width:200px;display:grid;gap:14px}
        .row{display:flex;align-items:center;gap:10px;font-size:15px}
        .row .material-symbols-outlined{color:#0ea5e9;font-size:20px}
        .row b{display:block;font-size:12px;color:#94a3b8;font-weight:600}
        .qr{text-align:center}
        .qr img{border:1px solid #e2e8f0;border-radius:12px;padding:8px;background:#fff}
        .qr-num{font-family:monospace;font-size:13px;color:#475569;margin-top:8px;word-break:break-all}
        .ticket__foot{border-top:2px dashed #e2e8f0;padding:16px 28px;display:flex;justify-content:space-between;align-items:center;font-size:14px;color:#64748b}
        .btn{display:inline-flex;align-items:center;gap:6px;background:#0ea5e9;color:#fff;border:none;border-radius:10px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none}
        .btn:hover{background:#0284c7}
        .nf{background:#fff;border-radius:16px;padding:48px;text-align:center;color:#64748b}
        @media print{.topbar,.btn,.back-link{display:none}.wrap{margin:0}}
    </style>
    <link rel="stylesheet" href="ui-polish.css">
</head>
<body>
    <div class="topbar">
        <a href="Evently-hub.php" class="logo"><span class="material-symbols-outlined">confirmation_number</span> Evently</a>
        <a href="my-tickets.php" class="back-link"><span class="material-symbols-outlined">arrow_back</span> Мои билеты</a>
    </div>

    <div class="wrap">
        <?php if (!empty($notFound)): ?>
            <div class="nf">
                <span class="material-symbols-outlined" style="font-size:48px;color:#cbd5e1">confirmation_number</span>
                <h2 style="margin:12px 0 6px">Билет не найден</h2>
                <p>Билет не существует или принадлежит другому пользователю.</p>
                <a href="my-tickets.php" class="btn" style="margin-top:16px">К моим билетам</a>
            </div>
        <?php else: ?>
            <div class="ticket">
                <div class="ticket__top">
                    <span class="ticket__cat"><?= e($category_names[$ticket['category']] ?? $ticket['category']) ?></span>
                    <div class="ticket__title"><?= e($ticket['title']) ?></div>
                </div>
                <div class="ticket__body">
                    <div class="ticket__info">
                        <div class="row"><span class="material-symbols-outlined">event</span><span><b>Дата</b><?= e($dateStr) ?><?= $ticket['start_time'] ? ', ' . e(substr($ticket['start_time'],0,5)) : '' ?></span></div>
                        <div class="row"><span class="material-symbols-outlined">location_on</span><span><b>Место проведения</b><?= e($ticket['venue_name']) ?>, <?= e($ticket['city']) ?></span></div>
                        <div class="row"><span class="material-symbols-outlined">event_seat</span><span><b>Место</b><?= e($seatStr) ?></span></div>
                        <div class="row"><span class="material-symbols-outlined">payments</span><span><b>Стоимость</b><?= e(number_format((float)$ticket['price_purchase'], 0, '.', ' ')) ?> ₽</span></div>
                    </div>
                    <div class="qr">
                        <img src="<?= e($qrUrl) ?>" alt="QR-код билета" width="180" height="180" loading="lazy">
                        <div class="qr-num"><?= e($ticket['ticket_number']) ?></div>
                    </div>
                </div>
                <div class="ticket__foot">
                    <span>Предъявите QR-код на входе</span>
                    <button class="btn" onclick="window.print()"><span class="material-symbols-outlined">print</span> Печать / PDF</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
