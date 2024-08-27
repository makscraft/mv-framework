SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `blocks` (
  `id` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `name` varchar(200) NOT NULL,
  `content` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `cache` (
  `key` varchar(200) NOT NULL,
  `content` longtext NOT NULL,
  `until` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `cache_clean` (
  `key` varchar(200) NOT NULL,
  `model` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `garbage` (
  `id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `row_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `content` longtext NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `row_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `operation` varchar(30) NOT NULL,
  `date` datetime NOT NULL,
  `name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `title` varchar(250) NOT NULL,
  `url` varchar(150) NOT NULL,
  `redirect` varchar(150) NOT NULL,
  `order` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `active` tinyint(4) NOT NULL,
  `in_menu` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `pages` (`id`, `parent`, `name`, `title`, `url`, `redirect`, `order`, `content`, `active`, `in_menu`) VALUES
(1, -1, 'Welcome', '', 'index', '', 1, '<h2>Models</h2>\r\n                              <p>Models are located at /models/ folder<br><a href=\"https://mv-framework.com/pre-installed-models\" target=\"_blank\">Read more about models</a></p>\r\n                              <h2>Views</h2>\r\n                              <p>Views (templates) are located at /views/ folder<br>\r\n                              <a href=\"https://mv-framework.com/creating-a-new-template\" target=\"_blank\">Read more about views</a></p>\r\n                              <h2>Routes</h2><p>Routes are listed in /config/routes.php file<br>\r\n                              <a href=\"https://mv-framework.com/general-principles-of-views\" target=\"_blank\">Read more about routing</a></p>', 1, 1),
(2, -1, '404 not found', '', 'e404', '', 5, '<p>The requested page was not found.</p>', 1, 0),
(3, -1, 'About', '', 'about', '', 2, '<p>Main idea of MV is to provide a simplified and faster way to create websites \r\n                              and web applications with the help of built-in CMF that allows to manage content with \r\n                              Admin Panel.</p><ul>\r\n                              <li>Totally object-oriented approach</li>\r\n                              <li>Autoloading of classes of models and plugins</li>\r\n                              <li>Abstraction of database</li>\r\n                              <li>MySQL and SQLite databases support</li>\r\n                              <li>Use of popular PHP patterns (Sigleton, Active Record)</li>\r\n                              <li>Updatable core and admin interface (reverse compatibility)</li></ul>\r\n                              <p>Automatically created the interface for content management. \r\n                              All active models have their own section in Admin Panel where it is possible to create, edit and delete records.</p>', 1, 1),
(4, -1, 'Documentation and support', '', 'documentation', '', 3, '<p>Documentation and code samples <a href=\"https://mv-framework.com\" target=\"_blank\">https://mv-framework.com</a></p>\r\n                              <p>Feedback form <a href=\"https://mv-framework.com/feedback\" target=\"_blank\">https://mv-framework.com/feedback</a></p>', 1, 1),
(5, -1, 'Form', '', 'form', '', 4, '', 1, 1);

CREATE TABLE `seo` (
  `key` varchar(100) NOT NULL,
  `value` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `settings` (`key`, `value`) VALUES
('files_counter', '1');

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(100) NOT NULL,
  `login` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_registered` datetime NOT NULL,
  `date_last_visit` datetime NOT NULL,
  `settings` longtext NOT NULL,
  `active` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `users` (`id`, `name`, `email`, `login`, `password`, `date_registered`, `date_last_visit`, `settings`, `active`) VALUES
(1, 'Root', '', 'root', '$2y$10$oHQF165kHAun.Qj97pkDVu2.RizSYKuzbtVwNcReKI6fVAU4jrXbi', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '', 1);

CREATE TABLE `users_logins` (
  `login` varchar(100) NOT NULL,
  `date` datetime NOT NULL,
  `user_agent` varchar(32) NOT NULL,
  `ip_address` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `users_passwords` (
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `password` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `users_rights` (
  `user_id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `create` tinyint(4) NOT NULL,
  `read` tinyint(4) NOT NULL,
  `update` tinyint(4) NOT NULL,
  `delete` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `users_sessions` (
  `user_id` int(11) NOT NULL,
  `session_id` varchar(32) NOT NULL,
  `ip_address` varchar(20) NOT NULL,
  `user_agent` varchar(32) NOT NULL,
  `last_hit` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `versions` (
  `id` int(11) NOT NULL,
  `model` varchar(100) NOT NULL,
  `row_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


ALTER TABLE `blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_active` (`id`,`active`);

ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

ALTER TABLE `cache_clean`
  ADD KEY `model` (`model`);

ALTER TABLE `garbage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module` (`module`);

ALTER TABLE `log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module` (`module`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_active` (`id`,`active`),
  ADD KEY `parent_active_in_menu` (`parent`,`active`,`in_menu`);

ALTER TABLE `seo`
  ADD PRIMARY KEY (`key`);

ALTER TABLE `settings`
  ADD UNIQUE KEY `key` (`key`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `email_active` (`email`,`active`);

ALTER TABLE `users_logins`
  ADD KEY `ip_date` (`ip_address`,`date`);

ALTER TABLE `users_passwords`
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `users_rights`
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `users_sessions`
  ADD UNIQUE KEY `users_sessions_all` (`user_id`,`session_id`,`ip_address`,`user_agent`),
  ADD UNIQUE KEY `users_sessions_uid_sid` (`user_id`,`session_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `model_row_id` (`model`,`row_id`),
  ADD KEY `model_version` (`model`,`version`),
  ADD KEY `model_row_id_version` (`model`,`row_id`,`version`);


ALTER TABLE `blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `garbage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
