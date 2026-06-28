<?php
session_start();

// Только администратор
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: Evently-hub.php');
    exit;
}

require __DIR__ . '/bd.php';
require __DIR__ . '/helpers.php';

// ----------------------------- ФИЛЬТРЫ ------------------------------------
$from     = $_GET['from']     ?? date('Y-m-d', strtotime('-12 months'));
$to       = $_GET['to']       ?? date('Y-m-d');
$category = $_GET['category'] ?? '';

// Безопасные значения дат
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : date('Y-m-d', strtotime('-12 months'));
$to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ? $to   : date('Y-m-d');

// Условие по категории (применяется к JOIN event)
$catSql = $category !== '' ? ' AND e.category = :category ' : '';
function bindCommon(PDOStatement $st, string $from, string $to, string $category): void {
    $st->bindValue(':from', $from . ' 00:00:00');
    $st->bindValue(':to',   $to   . ' 23:59:59');
    if ($category !== '') $st->bindValue(':category', $category);
}

// Список категорий для выпадающего фильтра
$catList = $pdo->query("SELECT DISTINCT category FROM event WHERE category IS NOT NULL AND category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$catRu = [
    'business'=>'Бизнес','music'=>'Музыка','education'=>'Образование','sports'=>'Спорт',
    'art'=>'Искусство','entertainment'=>'Развлечения','texh'=>'Технологии'
];
function catName(string $c, array $catRu): string { return $catRu[$c] ?? $c; }

// ----------------------- ВЫБОРКИ ОТЧЁТОВ ----------------------------------
// Базис: платежи (факт продаж) c JOIN на событие. «Выручка» = оплаченные платежи.

// 1. Выручка по событиям
$qEvents = "SELECT e.id_event, e.title, e.category,
                   COUNT(CASE WHEN p.status='paid' THEN 1 END) AS sold,
                   COALESCE(SUM(CASE WHEN p.status='paid' THEN p.amount END),0) AS revenue
            FROM event e
            LEFT JOIN payments p ON p.id_event=e.id_event AND p.created_at BETWEEN :from AND :to
            WHERE 1=1 $catSql
            GROUP BY e.id_event, e.title, e.category
            HAVING sold > 0
            ORDER BY revenue DESC";
$st = $pdo->prepare($qEvents); bindCommon($st, $from, $to, $category); $st->execute();
$rEvents = $st->fetchAll(PDO::FETCH_ASSOC);

// 2. Продажи и выручка по месяцам
$qMonths = "SELECT DATE_FORMAT(p.created_at,'%Y-%m') AS ym,
                   COUNT(CASE WHEN p.status='paid' THEN 1 END) AS sold,
                   COALESCE(SUM(CASE WHEN p.status='paid' THEN p.amount END),0) AS revenue
            FROM payments p JOIN event e ON e.id_event=p.id_event
            WHERE p.created_at BETWEEN :from AND :to $catSql
            GROUP BY ym ORDER BY ym";
$st = $pdo->prepare($qMonths); bindCommon($st, $from, $to, $category); $st->execute();
$rMonths = $st->fetchAll(PDO::FETCH_ASSOC);

// 3. Разбивка по категориям
$qCats = "SELECT e.category,
                 COUNT(DISTINCT e.id_event) AS events,
                 COUNT(CASE WHEN p.status='paid' THEN 1 END) AS sold,
                 COALESCE(SUM(CASE WHEN p.status='paid' THEN p.amount END),0) AS revenue
          FROM event e
          LEFT JOIN payments p ON p.id_event=e.id_event AND p.created_at BETWEEN :from AND :to
          WHERE 1=1 $catSql
          GROUP BY e.category ORDER BY revenue DESC";
$st = $pdo->prepare($qCats); bindCommon($st, $from, $to, $category); $st->execute();
$rCats = $st->fetchAll(PDO::FETCH_ASSOC);

// 4. Воронка статусов платежей
$qFunnel = "SELECT p.status,
                   COUNT(*) AS cnt,
                   COALESCE(SUM(p.amount),0) AS amount
            FROM payments p JOIN event e ON e.id_event=p.id_event
            WHERE p.created_at BETWEEN :from AND :to $catSql
            GROUP BY p.status ORDER BY cnt DESC";
$st = $pdo->prepare($qFunnel); bindCommon($st, $from, $to, $category); $st->execute();
$rFunnel = $st->fetchAll(PDO::FETCH_ASSOC);

// 5. Заполняемость залов
$qOcc = "SELECT e.id_event, e.title, e.total_seats,
                COUNT(CASE WHEN p.status='paid' THEN 1 END) AS sold
         FROM event e
         LEFT JOIN payments p ON p.id_event=e.id_event
         WHERE e.total_seats > 0 $catSql
         GROUP BY e.id_event, e.title, e.total_seats
         ORDER BY (COUNT(CASE WHEN p.status='paid' THEN 1 END)/e.total_seats) DESC";
$st = $pdo->prepare($qOcc); if ($category!=='') $st->bindValue(':category',$category); $st->execute();
$rOcc = $st->fetchAll(PDO::FETCH_ASSOC);

// 6. Пользователи по ролям
$rRoles = [
    'participant' => (int)$pdo->query("SELECT COUNT(*) FROM participants")->fetchColumn(),
    'organizer'   => (int)$pdo->query("SELECT COUNT(*) FROM organizers")->fetchColumn(),
    'admin'       => (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn(),
];

// KPI
$kpiRevenue = 0; $kpiSold = 0;
foreach ($rMonths as $m) { $kpiRevenue += (int)$m['revenue']; $kpiSold += (int)$m['sold']; }
$kpiActive = (int)$pdo->query("SELECT COUNT(*) FROM event WHERE status='active'")->fetchColumn();
$kpiUsers  = array_sum($rRoles);
$kpiAvgCheck = $kpiSold > 0 ? round($kpiRevenue / $kpiSold) : 0;

// ----------------------------- ЭКСПОРТ CSV --------------------------------
if (isset($_GET['export'])) {
    $map = [
        'events'     => [['Событие','Категория','Продано','Выручка ₽'], array_map(fn($r)=>[$r['title'],catName($r['category'],$catRu),$r['sold'],$r['revenue']], $rEvents)],
        'months'     => [['Месяц','Продано','Выручка ₽'], array_map(fn($r)=>[$r['ym'],$r['sold'],$r['revenue']], $rMonths)],
        'categories' => [['Категория','Событий','Продано','Выручка ₽'], array_map(fn($r)=>[catName($r['category'],$catRu),$r['events'],$r['sold'],$r['revenue']], $rCats)],
        'occupancy'  => [['Событие','Мест всего','Продано','Заполнено %'], array_map(fn($r)=>[$r['title'],$r['total_seats'],$r['sold'], $r['total_seats']>0?round(100*$r['sold']/$r['total_seats']):0], $rOcc)],
        'funnel'     => [['Статус платежа','Кол-во','Сумма ₽'], array_map(fn($r)=>[$r['status'],$r['cnt'],$r['amount']], $rFunnel)],
    ];
    $key = $_GET['export'];
    if (isset($map[$key])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evently_' . $key . '_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM для Excel
        $out = fopen('php://output', 'w');
        fputcsv($out, $map[$key][0], ';');
        foreach ($map[$key][1] as $row) fputcsv($out, $row, ';');
        fclose($out);
        exit;
    }
}

// Хелпер: максимум для нормировки графика
function maxOf(array $rows, string $col): float { $m=0; foreach($rows as $r){ $m=max($m,(float)$r[$col]); } return $m ?: 1; }
function money(int $v): string { return number_format($v, 0, ',', ' '); }
$qs = http_build_query(['from'=>$from,'to'=>$to,'category'=>$category]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёты | Evently</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-page.css">
    <link rel="stylesheet" href="ui-polish.css">
    <link rel="stylesheet" href="admin-reports.css">
</head>
<body>
    <header class="header">
        <div class="header__left">
            <a href="Evently-hub.php" class="logo">
                <span class="logo__icon material-symbols-outlined">event_available</span>
                <span class="logo__text">Evently</span>
            </a>
            <nav class="nav">
                <a href="admin-event.php" class="nav__link">Модерация</a>
                <a href="admin-page.php" class="nav__link">Пользователи</a>
                <a href="admin-reports.php" class="nav__link nav__link--active">Отчёты</a>
            </nav>
        </div>
        <div class="header__right">
            <div class="teg-name"><?= e($_SESSION['name'] ?? 'Админ') ?></div>
        </div>
    </header>

    <div class="app-wrapper">
        <aside class="sidebar">
            <div class="sidebar__header"><h2>Admin Panel</h2><p>System Control</p></div>
            <nav class="sidebar__nav">
                <a href="admin-page.php" class="sidebar__link"><span class="material-symbols-outlined">grid_view</span> Панель управления</a>
                <a href="add-user.php" class="sidebar__link"><span class="material-symbols-outlined">person</span> Пользователи</a>
                <a href="admin-event.php" class="sidebar__link"><span class="material-symbols-outlined">verified</span> Модерация событий</a>
                <a href="admin-reports.php" class="sidebar__link sidebar__link--active"><span class="material-symbols-outlined">monitoring</span> Отчёты</a>
            </nav>
        </aside>

        <main class="rp-main">
            <div class="rp-head">
                <h1>Отчёты и аналитика</h1>
                <p class="rp-sub">Период, продажи, выручка, заполняемость и аудитория. Любой отчёт выгружается в CSV.</p>
            </div>

            <!-- Фильтры -->
            <form class="rp-filters" method="get">
                <div class="rp-field">
                    <label>С даты</label>
                    <input type="date" name="from" value="<?= e($from) ?>">
                </div>
                <div class="rp-field">
                    <label>По дату</label>
                    <input type="date" name="to" value="<?= e($to) ?>">
                </div>
                <div class="rp-field">
                    <label>Категория</label>
                    <select name="category">
                        <option value="">Все категории</option>
                        <?php foreach ($catList as $c): ?>
                            <option value="<?= e($c) ?>" <?= $c===$category?'selected':'' ?>><?= e(catName($c,$catRu)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="rp-btn" type="submit"><span class="material-symbols-outlined">filter_alt</span> Применить</button>
                <a class="rp-btn rp-btn--ghost" href="admin-reports.php"><span class="material-symbols-outlined">restart_alt</span> Сброс</a>
            </form>

            <!-- KPI -->
            <div class="rp-kpis">
                <div class="rp-kpi"><div class="rp-kpi__v"><?= money($kpiRevenue) ?> ₽</div><div class="rp-kpi__l">Выручка за период</div></div>
                <div class="rp-kpi"><div class="rp-kpi__v"><?= $kpiSold ?></div><div class="rp-kpi__l">Билетов продано</div></div>
                <div class="rp-kpi"><div class="rp-kpi__v"><?= money($kpiAvgCheck) ?> ₽</div><div class="rp-kpi__l">Средний чек</div></div>
                <div class="rp-kpi"><div class="rp-kpi__v"><?= $kpiActive ?></div><div class="rp-kpi__l">Активных событий</div></div>
                <div class="rp-kpi"><div class="rp-kpi__v"><?= $kpiUsers ?></div><div class="rp-kpi__l">Пользователей</div></div>
            </div>

            <!-- Отчёт 1: выручка по событиям -->
            <section class="rp-card">
                <div class="rp-card__head">
                    <h2>Выручка по событиям</h2>
                    <a class="rp-export" href="?<?= $qs ?>&export=events"><span class="material-symbols-outlined">download</span> CSV</a>
                </div>
                <?php $mx = maxOf($rEvents,'revenue'); ?>
                <table class="rp-table">
                    <thead><tr><th>Событие</th><th>Категория</th><th class="num">Продано</th><th class="num">Выручка</th><th>Доля</th></tr></thead>
                    <tbody>
                    <?php foreach ($rEvents as $r): ?>
                        <tr>
                            <td><?= e($r['title']) ?></td>
                            <td><span class="rp-tag"><?= e(catName($r['category'],$catRu)) ?></span></td>
                            <td class="num"><?= (int)$r['sold'] ?></td>
                            <td class="num"><b><?= money((int)$r['revenue']) ?> ₽</b></td>
                            <td><div class="rp-bar"><span style="width: <?= round(100*$r['revenue']/$mx) ?>%"></span></div></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rEvents): ?><tr><td colspan="5" class="rp-empty">Нет данных за выбранный период.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Отчёт 2: динамика по месяцам -->
            <section class="rp-card">
                <div class="rp-card__head">
                    <h2>Динамика продаж по месяцам</h2>
                    <a class="rp-export" href="?<?= $qs ?>&export=months"><span class="material-symbols-outlined">download</span> CSV</a>
                </div>
                <?php $mx = maxOf($rMonths,'revenue'); ?>
                <div class="rp-chart">
                    <?php foreach ($rMonths as $r): ?>
                        <div class="rp-col" title="<?= e($r['ym']) ?>: <?= money((int)$r['revenue']) ?> ₽, продано <?= (int)$r['sold'] ?>">
                            <div class="rp-col__bar" style="height: <?= max(4, round(160*$r['revenue']/$mx)) ?>px"></div>
                            <div class="rp-col__val"><?= money((int)$r['revenue']) ?></div>
                            <div class="rp-col__lbl"><?= e($r['ym']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$rMonths): ?><div class="rp-empty">Нет данных за период.</div><?php endif; ?>
                </div>
            </section>

            <div class="rp-grid2">
                <!-- Отчёт 3: по категориям -->
                <section class="rp-card">
                    <div class="rp-card__head">
                        <h2>По категориям</h2>
                        <a class="rp-export" href="?<?= $qs ?>&export=categories"><span class="material-symbols-outlined">download</span> CSV</a>
                    </div>
                    <?php $mx = maxOf($rCats,'revenue'); ?>
                    <table class="rp-table">
                        <thead><tr><th>Категория</th><th class="num">Событий</th><th class="num">Продано</th><th class="num">Выручка</th></tr></thead>
                        <tbody>
                        <?php foreach ($rCats as $r): ?>
                            <tr>
                                <td><span class="rp-tag"><?= e(catName($r['category'],$catRu)) ?></span><div class="rp-bar sm"><span style="width: <?= round(100*$r['revenue']/$mx) ?>%"></span></div></td>
                                <td class="num"><?= (int)$r['events'] ?></td>
                                <td class="num"><?= (int)$r['sold'] ?></td>
                                <td class="num"><b><?= money((int)$r['revenue']) ?> ₽</b></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <!-- Отчёт 4: воронка платежей -->
                <section class="rp-card">
                    <div class="rp-card__head">
                        <h2>Статусы платежей</h2>
                        <a class="rp-export" href="?<?= $qs ?>&export=funnel"><span class="material-symbols-outlined">download</span> CSV</a>
                    </div>
                    <?php $stRu=['paid'=>'Оплачено','pending'=>'Ожидает','canceled'=>'Отменено']; $mx=maxOf($rFunnel,'cnt'); ?>
                    <table class="rp-table">
                        <thead><tr><th>Статус</th><th class="num">Кол-во</th><th class="num">Сумма</th><th>Доля</th></tr></thead>
                        <tbody>
                        <?php foreach ($rFunnel as $r): ?>
                            <tr>
                                <td><span class="rp-dot rp-dot--<?= e($r['status']) ?>"></span> <?= e($stRu[$r['status']] ?? $r['status']) ?></td>
                                <td class="num"><?= (int)$r['cnt'] ?></td>
                                <td class="num"><?= money((int)$r['amount']) ?> ₽</td>
                                <td><div class="rp-bar"><span class="st-<?= e($r['status']) ?>" style="width: <?= round(100*$r['cnt']/$mx) ?>%"></span></div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- Отчёт 5: заполняемость -->
            <section class="rp-card">
                <div class="rp-card__head">
                    <h2>Заполняемость залов</h2>
                    <a class="rp-export" href="?<?= $qs ?>&export=occupancy"><span class="material-symbols-outlined">download</span> CSV</a>
                </div>
                <table class="rp-table">
                    <thead><tr><th>Событие</th><th class="num">Мест</th><th class="num">Продано</th><th>Заполнено</th></tr></thead>
                    <tbody>
                    <?php foreach ($rOcc as $r): $pct = $r['total_seats']>0 ? round(100*$r['sold']/$r['total_seats']) : 0; ?>
                        <tr>
                            <td><?= e($r['title']) ?></td>
                            <td class="num"><?= (int)$r['total_seats'] ?></td>
                            <td class="num"><?= (int)$r['sold'] ?></td>
                            <td>
                                <div class="rp-occ">
                                    <div class="rp-bar"><span class="<?= $pct>=80?'st-high':($pct>=40?'st-mid':'st-low') ?>" style="width: <?= min(100,$pct) ?>%"></span></div>
                                    <b><?= $pct ?>%</b>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rOcc): ?><tr><td colspan="4" class="rp-empty">Нет событий с фиксированными местами.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Отчёт 6: аудитория по ролям -->
            <section class="rp-card">
                <div class="rp-card__head"><h2>Аудитория по ролям</h2></div>
                <?php $mxR = max(1, max($rRoles)); $rolRu=['participant'=>'Участники','organizer'=>'Организаторы','admin'=>'Администраторы']; ?>
                <div class="rp-roles">
                    <?php foreach ($rRoles as $role=>$cnt): ?>
                        <div class="rp-role">
                            <div class="rp-role__top"><span><?= e($rolRu[$role]) ?></span><b><?= $cnt ?></b></div>
                            <div class="rp-bar"><span style="width: <?= round(100*$cnt/$mxR) ?>%"></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="rp-foot">Evently · отчёты сформированы <?= date('d.m.Y H:i') ?> · период <?= e($from) ?> — <?= e($to) ?></div>
        </main>
    </div>
</body>
</html>
