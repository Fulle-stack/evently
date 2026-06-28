-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Июн 28 2026 г., 23:56
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `evently`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `email` varchar(70) NOT NULL,
  `password` varchar(256) NOT NULL,
  `role` enum('admin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(4, 'Соколов Максим Платонович', 'kivevid147@anawebs.com', '$2y$10$FsRKxHlj3aeHjZ6MHfr.y.UY2vdVsRkKD0KkUpyXzfHCgpJRL3rC2', 'admin', '2026-05-11 18:47:26');

-- --------------------------------------------------------

--
-- Структура таблицы `event`
--

CREATE TABLE `event` (
  `id_event` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `title` varchar(50) NOT NULL,
  `organizer_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `street` varchar(50) NOT NULL,
  `district` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `venue_name` varchar(100) NOT NULL,
  `floor` int(11) DEFAULT NULL,
  `entrance` varchar(11) NOT NULL,
  `region` varchar(50) NOT NULL,
  `price` int(11) UNSIGNED NOT NULL,
  `status` enum('moderation','active','rejected') NOT NULL DEFAULT 'moderation',
  `speakers` varchar(255) NOT NULL,
  `described` varchar(512) NOT NULL,
  `id` int(11) NOT NULL,
  `total_seats` int(11) DEFAULT 0,
  `registration_open` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `event`
--

INSERT INTO `event` (`id_event`, `photo`, `title`, `organizer_name`, `category`, `event_type`, `start_date`, `start_time`, `end_time`, `street`, `district`, `city`, `country`, `venue_name`, `floor`, `entrance`, `region`, `price`, `status`, `speakers`, `described`, `id`, `total_seats`, `registration_open`) VALUES
(26, 'uploads/6a18532075055_1779979040.png', 'IT-конференция «Будущее технологий»', 'TechCorp', 'business', 'conference', '2026-06-15', '10:00:00', '18:00:00', 'Краснопресненская наб., 12', '', 'Москва', 'Россия', 'Центр международной торговли', 2, '1', 'capital', 3500, 'active', 'Алексей Смирнов (CEO TechCorp), Мария Иванова (руководитель AI-лаборатории), Дмитрий Волков (эксперт по кибербезопасности)', 'Крупнейшая IT-конференция для предпринимателей и разработчиков. В программе: доклады о будущем искусственного интеллекта, блокчейн-технологий и кибербезопасности. Участники смогут посетить мастер-классы, networking-зону и выставку стартапов.', 15, 50, 1),
(27, 'uploads/6a1859decbb18_1779980766.png', 'Летний музыкальный фестиваль «Sunset Fest»', 'SunsetFest', 'music', 'festival', '2026-07-22', '16:00:00', '23:00:00', 'Петровская коса, 1', '', 'Санкт-Петербург', 'Россия', 'Открытая сцена на берегу Финского залива', NULL, '', '', 2000, 'active', 'Группа «Вечерний город», \r\nDJ Alex Wave,\r\nАкустический дуэт «Две луны»', 'Главное музыкальное событие лета! Живые выступления популярных инди-групп, электронная сцена, фуд-корт с уличной едой и лаунж-зоны с видом на закат. Приходите с друзьями, чтобы насладиться атмосферой свободы и творчества.', 15, 0, 0),
(28, 'uploads/6a185b1099574_1779981072.png', 'Воркшоп «Создай свой первый сайт за 3 часа»', 'Анна Котова', 'education', 'workshop', '2026-08-05', '12:00:00', '15:00:00', 'ул. Петербургская, 52', 'Вахитовский', 'Казань', 'Россия', 'Коворкинг «Точка роста»', 0, 'нет', 'capital', 500, 'active', 'Анна Котова (веб-разработчик, преподаватель), Илья Зайцев (UX/UI дизайнер)', 'Практический воркшоп для начинающих. За три часа вы создадите свой первый лендинг с нуля: освоите HTML, CSS и базовые принципы веб-дизайна. Все участники получат готовый сайт и сертификат о прохождении. Ноутбуки предоставляются.', 15, 24, 1),
(29, 'uploads/6a185da103b68_1779981729.png', 'тетс', 'тс', 'education', 'meetup', '2026-05-08', '18:23:00', '22:21:00', 'с', 'с', 'с', 'с', 'сс', NULL, '', 'regional', 0, 'rejected', 'сс', 'сс', 15, 5, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `organizers`
--

CREATE TABLE `organizers` (
  `id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `email` varchar(70) NOT NULL,
  `phone` varchar(20) NOT NULL DEFAULT '',
  `password` varchar(256) NOT NULL,
  `role` enum('participants','organizer','admin') NOT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `organizers`
--

INSERT INTO `organizers` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(15, 'Иван Шухин', 'nowag@koletter.com', '', '$2y$10$FsRKxHlj3aeHjZ6MHfr.y.UY2vdVsRkKD0KkUpyXzfHCgpJRL3rC2', 'organizer', '2026-03-15 10:05:21.383222');

-- --------------------------------------------------------

--
-- Структура таблицы `participants`
--

CREATE TABLE `participants` (
  `id_participants` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(70) NOT NULL,
  `password` varchar(256) NOT NULL,
  `phone` varchar(15) NOT NULL DEFAULT '',
  `surname` varchar(50) NOT NULL DEFAULT '',
  `role` enum('organizer','participant','admin') NOT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `participants`
--

INSERT INTO `participants` (`id_participants`, `name`, `email`, `password`, `phone`, `surname`, `role`, `created_at`) VALUES
(9, 'Новиков Дмитрий Антонович', 'dhdhdhgdueh@gmail.com', '$2y$10$FsRKxHlj3aeHjZ6MHfr.y.UY2vdVsRkKD0KkUpyXzfHCgpJRL3rC2', '', '', 'participant', '2026-05-30 18:50:38.045443');

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `id_payment` int(11) NOT NULL,
  `amount` int(11) UNSIGNED NOT NULL,
  `status` varchar(32) NOT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `id_event` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `payments`
--

INSERT INTO `payments` (`id_payment`, `amount`, `status`, `created_at`, `id_event`) VALUES
(36, 500, 'pending', '2026-05-30 18:51:00.636421', 28),
(37, 7000, 'pending', '2026-05-30 19:00:21.415262', 26);

-- --------------------------------------------------------

--
-- Структура таблицы `questions`
--

CREATE TABLE `questions` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `description` varchar(512) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `questions`
--

INSERT INTO `questions` (`id`, `name`, `phone`, `description`, `created_at`) VALUES
(18, 'Олег', '+79160834567', 'Имеется спорная точка зрения, гласящая примерно следующее: многие известные личности лишь добавляют фракционных разногласий и указаны как претенденты на роль ключевых факторов.', '2026-03-18 18:30:03');

-- --------------------------------------------------------

--
-- Структура таблицы `reservation`
--

CREATE TABLE `reservation` (
  `id_participants` int(11) NOT NULL,
  `id_event` int(11) NOT NULL,
  `id_payment` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `reservation`
--

INSERT INTO `reservation` (`id_participants`, `id_event`, `id_payment`) VALUES
(9, 26, 37),
(9, 28, 36);

-- --------------------------------------------------------

--
-- Структура таблицы `seats`
--

CREATE TABLE `seats` (
  `id_seat` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `seat_row` varchar(2) NOT NULL,
  `seat_number` int(11) NOT NULL,
  `price` int(11) UNSIGNED NOT NULL,
  `status` enum('free','booked','sold') DEFAULT 'free',
  `booked_by` int(11) DEFAULT NULL,
  `booked_until` timestamp NULL DEFAULT NULL,
  `reserved_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `seats`
--

INSERT INTO `seats` (`id_seat`, `event_id`, `seat_row`, `seat_number`, `price`, `status`, `booked_by`, `booked_until`, `reserved_until`) VALUES
(237, 26, 'A', 1, 3500, 'free', NULL, NULL, NULL),
(238, 26, 'A', 2, 3500, 'free', NULL, NULL, NULL),
(239, 26, 'A', 3, 3500, 'sold', NULL, NULL, NULL),
(240, 26, 'A', 4, 3500, 'sold', NULL, NULL, NULL),
(241, 26, 'A', 5, 3500, 'free', NULL, NULL, NULL),
(242, 26, 'A', 6, 3500, 'free', NULL, NULL, NULL),
(243, 26, 'A', 7, 3500, 'free', NULL, NULL, NULL),
(244, 26, 'A', 8, 3500, 'free', NULL, NULL, NULL),
(245, 26, 'A', 9, 3500, 'free', NULL, NULL, NULL),
(246, 26, 'A', 10, 3500, 'free', NULL, NULL, NULL),
(247, 26, 'B', 1, 3500, 'free', NULL, NULL, NULL),
(248, 26, 'B', 2, 3500, 'free', NULL, NULL, NULL),
(249, 26, 'B', 3, 3500, 'free', NULL, NULL, NULL),
(250, 26, 'B', 4, 3500, 'free', NULL, NULL, NULL),
(251, 26, 'B', 5, 3500, 'free', NULL, NULL, NULL),
(252, 26, 'B', 6, 3500, 'free', NULL, NULL, NULL),
(253, 26, 'B', 7, 3500, 'free', NULL, NULL, NULL),
(254, 26, 'B', 8, 3500, 'free', NULL, NULL, NULL),
(255, 26, 'B', 9, 3500, 'free', NULL, NULL, NULL),
(256, 26, 'B', 10, 3500, 'free', NULL, NULL, NULL),
(257, 26, 'C', 1, 3500, 'free', NULL, NULL, NULL),
(258, 26, 'C', 2, 3500, 'free', NULL, NULL, NULL),
(259, 26, 'C', 3, 3500, 'free', NULL, NULL, NULL),
(260, 26, 'C', 4, 3500, 'free', NULL, NULL, NULL),
(261, 26, 'C', 5, 3500, 'free', NULL, NULL, NULL),
(262, 26, 'C', 6, 3500, 'free', NULL, NULL, NULL),
(263, 26, 'C', 7, 3500, 'free', NULL, NULL, NULL),
(264, 26, 'C', 8, 3500, 'free', NULL, NULL, NULL),
(265, 26, 'C', 9, 3500, 'free', NULL, NULL, NULL),
(266, 26, 'C', 10, 3500, 'free', NULL, NULL, NULL),
(267, 26, 'D', 1, 3500, 'free', NULL, NULL, NULL),
(268, 26, 'D', 2, 3500, 'free', NULL, NULL, NULL),
(269, 26, 'D', 3, 3500, 'free', NULL, NULL, NULL),
(270, 26, 'D', 4, 3500, 'free', NULL, NULL, NULL),
(271, 26, 'D', 5, 3500, 'free', NULL, NULL, NULL),
(272, 26, 'D', 6, 3500, 'free', NULL, NULL, NULL),
(273, 26, 'D', 7, 3500, 'free', NULL, NULL, NULL),
(274, 26, 'D', 8, 3500, 'free', NULL, NULL, NULL),
(275, 26, 'D', 9, 3500, 'free', NULL, NULL, NULL),
(276, 26, 'D', 10, 3500, 'free', NULL, NULL, NULL),
(277, 26, 'E', 1, 3500, 'free', NULL, NULL, NULL),
(278, 26, 'E', 2, 3500, 'free', NULL, NULL, NULL),
(279, 26, 'E', 3, 3500, 'free', NULL, NULL, NULL),
(280, 26, 'E', 4, 3500, 'free', NULL, NULL, NULL),
(281, 26, 'E', 5, 3500, 'free', NULL, NULL, NULL),
(282, 26, 'E', 6, 3500, 'free', NULL, NULL, NULL),
(283, 26, 'E', 7, 3500, 'free', NULL, NULL, NULL),
(284, 26, 'E', 8, 3500, 'free', NULL, NULL, NULL),
(285, 26, 'E', 9, 3500, 'free', NULL, NULL, NULL),
(286, 26, 'E', 10, 3500, 'free', NULL, NULL, NULL),
(287, 28, 'A', 1, 500, 'sold', NULL, NULL, NULL),
(288, 28, 'A', 2, 500, 'free', NULL, NULL, NULL),
(289, 28, 'A', 3, 500, 'free', NULL, NULL, NULL),
(290, 28, 'A', 4, 500, 'free', NULL, NULL, NULL),
(291, 28, 'A', 5, 500, 'free', NULL, NULL, NULL),
(292, 28, 'A', 6, 500, 'free', NULL, NULL, NULL),
(293, 28, 'A', 7, 500, 'free', NULL, NULL, NULL),
(294, 28, 'A', 8, 500, 'free', NULL, NULL, NULL),
(295, 28, 'B', 1, 500, 'free', NULL, NULL, NULL),
(296, 28, 'B', 2, 500, 'free', NULL, NULL, NULL),
(297, 28, 'B', 3, 500, 'free', NULL, NULL, NULL),
(298, 28, 'B', 4, 500, 'free', NULL, NULL, NULL),
(299, 28, 'B', 5, 500, 'free', NULL, NULL, NULL),
(300, 28, 'B', 6, 500, 'free', NULL, NULL, NULL),
(301, 28, 'B', 7, 500, 'free', NULL, NULL, NULL),
(302, 28, 'B', 8, 500, 'free', NULL, NULL, NULL),
(303, 28, 'C', 1, 500, 'free', NULL, NULL, NULL),
(304, 28, 'C', 2, 500, 'free', NULL, NULL, NULL),
(305, 28, 'C', 3, 500, 'free', NULL, NULL, NULL),
(306, 28, 'C', 4, 500, 'free', NULL, NULL, NULL),
(307, 28, 'C', 5, 500, 'free', NULL, NULL, NULL),
(308, 28, 'C', 6, 500, 'free', NULL, NULL, NULL),
(309, 28, 'C', 7, 500, 'free', NULL, NULL, NULL),
(310, 28, 'C', 8, 500, 'free', NULL, NULL, NULL),
(311, 29, 'A', 1, 0, 'free', NULL, NULL, NULL),
(312, 29, 'A', 2, 0, 'free', NULL, NULL, NULL),
(313, 29, 'A', 3, 0, 'free', NULL, NULL, NULL),
(314, 29, 'A', 4, 0, 'free', NULL, NULL, NULL),
(315, 29, 'A', 5, 0, 'free', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `tickets`
--

CREATE TABLE `tickets` (
  `id_tickets` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `event_id` int(11) NOT NULL,
  `price_purchase` int(11) NOT NULL,
  `status` varchar(11) NOT NULL,
  `id_payment` int(11) DEFAULT NULL,
  `seat_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `tickets`
--

INSERT INTO `tickets` (`id_tickets`, `ticket_number`, `event_id`, `price_purchase`, `status`, `id_payment`, `seat_id`) VALUES
(45, 'TK-1780167060-4852', 28, 500, 'active', 36, 287),
(46, 'TK-1780167621-6616', 26, 3500, 'active', 37, 239),
(47, 'TK-1780167621-9573', 26, 3500, 'active', 37, 240);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`id_event`),
  ADD KEY `id` (`id`);

--
-- Индексы таблицы `organizers`
--
ALTER TABLE `organizers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- Индексы таблицы `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id_participants`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id_payment`),
  ADD KEY `id_event` (`id_event`);

--
-- Индексы таблицы `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id_participants`,`id_event`,`id_payment`),
  ADD KEY `id_event` (`id_event`),
  ADD KEY `id_payment` (`id_payment`);

--
-- Индексы таблицы `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`id_seat`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `seats_ibfk_user` (`booked_by`);

--
-- Индексы таблицы `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id_tickets`),
  ADD KEY `tickets_ibfk_payment` (`id_payment`),
  ADD KEY `seat_id` (`seat_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `event`
--
ALTER TABLE `event`
  MODIFY `id_event` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT для таблицы `organizers`
--
ALTER TABLE `organizers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `participants`
--
ALTER TABLE `participants`
  MODIFY `id_participants` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id_payment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT для таблицы `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id_participants` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `seats`
--
ALTER TABLE `seats`
  MODIFY `id_seat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=316;

--
-- AUTO_INCREMENT для таблицы `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id_tickets` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`id`) REFERENCES `organizers` (`id`);

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`id_event`) REFERENCES `event` (`id_event`);

--
-- Ограничения внешнего ключа таблицы `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`id_participants`) REFERENCES `participants` (`id_participants`),
  ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`id_event`) REFERENCES `event` (`id_event`),
  ADD CONSTRAINT `reservation_ibfk_3` FOREIGN KEY (`id_payment`) REFERENCES `payments` (`id_payment`);

--
-- Ограничения внешнего ключа таблицы `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_ibfk_event` FOREIGN KEY (`event_id`) REFERENCES `event` (`id_event`),
  ADD CONSTRAINT `seats_ibfk_user` FOREIGN KEY (`booked_by`) REFERENCES `participants` (`id_participants`);

--
-- Ограничения внешнего ключа таблицы `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_payment` FOREIGN KEY (`id_payment`) REFERENCES `payments` (`id_payment`),
  ADD CONSTRAINT `tickets_ibfk_seat` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id_seat`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
