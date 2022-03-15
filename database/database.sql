--
-- Структура таблицы `automatic_blocks`
--

CREATE TABLE `automatic_blocks` (
  `id` int NOT NULL,
  `ip` varchar(100) NOT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `blocks`
--

CREATE TABLE `blocks` (
  `id` int NOT NULL,
  `host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_hostname` int NOT NULL DEFAULT '0',
  `is_period` tinyint NOT NULL DEFAULT '0',
  `period_start` bigint DEFAULT NULL,
  `period_stop` bigint DEFAULT NULL,
  `is_block` int NOT NULL DEFAULT '0' COMMENT '0 - Разблокирован, 1 - Заблокирован',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `block_configs`
--

CREATE TABLE `block_configs` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `block_configs`
--

INSERT INTO `block_configs` (`id`, `name`, `value`) VALUES
(1, 'COUNT_REQUESTS_TO_AUTO_BLOCK', '3');

-- --------------------------------------------------------

--
-- Структура таблицы `statistics`
--

CREATE TABLE `statistics` (
  `id` int NOT NULL,
  `date` date DEFAULT NULL COMMENT 'Дата посещения',
  `ip` varchar(150) DEFAULT NULL COMMENT 'IP адрес',
  `hostname` varchar(255) DEFAULT NULL COMMENT 'Имя хоста',
  `visits` int NOT NULL DEFAULT '0' COMMENT 'Количество посещений',
  `requests` int NOT NULL DEFAULT '0' COMMENT 'Количество попыток оставить заявку',
  `visits_drops` int NOT NULL DEFAULT '0' COMMENT 'Количество блокированных посещений'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `visits`
--

CREATE TABLE `visits` (
  `id` int NOT NULL,
  `ip` varchar(150) DEFAULT NULL,
  `is_blocked` int NOT NULL DEFAULT '0' COMMENT '1 - Блокированный вход',
  `page` text,
  `method` varchar(50) DEFAULT NULL,
  `referer` text,
  `user_agent` text,
  `request_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `automatic_blocks`
--
ALTER TABLE `automatic_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_date` (`ip`,`date`);

--
-- Индексы таблицы `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `host_is_block` (`host`,`is_block`),
  ADD KEY `is_hostname_is_block` (`is_hostname`,`is_block`),
  ADD KEY `is_period_is_block` (`is_period`,`is_block`),
  ADD KEY `period` (`period_start`,`period_stop`);

--
-- Индексы таблицы `block_configs`
--
ALTER TABLE `block_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Индексы таблицы `statistics`
--
ALTER TABLE `statistics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date_ip` (`date`,`ip`);

--
-- Индексы таблицы `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `automatic_blocks`
--
ALTER TABLE `automatic_blocks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
  
--
-- AUTO_INCREMENT для таблицы `blocks`
--
ALTER TABLE `blocks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `block_configs`
--
ALTER TABLE `block_configs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
  
--
-- AUTO_INCREMENT для таблицы `statistics`
--
ALTER TABLE `statistics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;