-- Host: 172.17.0.1:3306
-- Generation Time: Mar 22, 2024 at 09:45 AM
-- Server version: 10.11.7-MariaDB-1:10.11.7+maria~ubu2204
-- PHP Version: 8.2.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` uuid NOT NULL DEFAULT uuid() COMMENT 'account UID (UUID)',
  `app_id` uuid NOT NULL,
  `username` varchar(120) NOT NULL COMMENT 'username',
  `password` varchar(120) DEFAULT NULL COMMENT 'user password',
  `status` varchar(120) DEFAULT NULL COMMENT 'user status',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'user created at',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'user updated at',
  `deleted_at` datetime DEFAULT NULL COMMENT 'user deletd at'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='accounts table';

-- --------------------------------------------------------

--
-- Table structure for table `account_meta`
--

CREATE TABLE `account_meta` (
  `account_id` uuid NOT NULL COMMENT 'account id',
  `name` varchar(255) NOT NULL COMMENT 'meta name',
  `data` longtext DEFAULT NULL COMMENT 'metadata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='account metadata';

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) NOT NULL COMMENT 'activity id',
  `name` varchar(255) NOT NULL COMMENT 'activity name',
  `user_id` bigint(20) DEFAULT NULL COMMENT 'user id (null is system)',
  `type` varchar(255) DEFAULT NULL COMMENT 'activity type',
  `data` longtext DEFAULT NULL COMMENT 'activity data',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'activity created at'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='activity log';

-- --------------------------------------------------------

--
-- Table structure for table `apps`
--

CREATE TABLE `apps` (
  `id` uuid NOT NULL DEFAULT uuid() COMMENT 'app id (uuid)',
  `name` varchar(255) NOT NULL COMMENT 'app name',
  `password` varchar(120) DEFAULT NULL COMMENT 'app password',
  `owner` bigint(20) NOT NULL COMMENT 'app owner',
  `token` uuid NOT NULL DEFAULT uuid() COMMENT 'app token (uuid)',
  `secret` varchar(120) DEFAULT NULL COMMENT 'authenticator secret code',
  `status` varchar(120) NOT NULL DEFAULT 'active' COMMENT 'app status',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'app created at',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'app updated at',
  `deleted_at` datetime DEFAULT NULL COMMENT 'app deleted at'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='applications table (users owned)';

-- --------------------------------------------------------

--
-- Table structure for table `app_meta`
--

CREATE TABLE `app_meta` (
  `app_id` uuid NOT NULL COMMENT 'application id',
  `name` varchar(255) NOT NULL COMMENT 'meta name',
  `data` longtext DEFAULT NULL COMMENT 'metadata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='application metadata';

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` bigint(20) NOT NULL COMMENT 'log id',
  `channel` varchar(255) NOT NULL DEFAULT 'default' COMMENT 'log channel',
  `level` varchar(20) NOT NULL COMMENT 'log level',
  `message` mediumtext NOT NULL COMMENT 'log message',
  `context` mediumtext DEFAULT NULL COMMENT 'log context',
  `extra` mediumtext DEFAULT NULL COMMENT 'log extra',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'log created at'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='logs table (for monolog)';

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `name` varchar(255) NOT NULL COMMENT 'primary option name',
  `data` longtext DEFAULT NULL COMMENT 'data'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='site options';

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` uuid NOT NULL DEFAULT uuid(),
  `name` varchar(40) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='member role (for access)';

-- --------------------------------------------------------

--
-- Table structure for table `role_access`
--

CREATE TABLE `role_access` (
  `id` uuid NOT NULL DEFAULT uuid(),
  `name` varchar(120) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='role access';

-- --------------------------------------------------------

--
-- Table structure for table `role_meta`
--

CREATE TABLE `role_meta` (
  `role_id` uuid NOT NULL,
  `role_access_id` uuid NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='role meta for role & access';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL COMMENT 'user id',
  `username` varchar(120) NOT NULL COMMENT 'username',
  `email` varchar(255) NOT NULL COMMENT 'email address',
  `password` varchar(120) DEFAULT NULL COMMENT 'user password',
  `first_name` varchar(120) NOT NULL COMMENT 'user first name',
  `last_name` varchar(120) DEFAULT NULL COMMENT 'user last name',
  `role` varchar(40) DEFAULT NULL COMMENT 'user role name',
  `token` uuid NOT NULL DEFAULT uuid() COMMENT 'user token',
  `secret` varchar(120) DEFAULT NULL COMMENT 'authenticator secret code',
  `status` varchar(120) DEFAULT NULL COMMENT 'user status',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'user created at',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'user updated at',
  `deleted_at` datetime DEFAULT NULL COMMENT 'user deleted at'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='user table';

-- --------------------------------------------------------

--
-- Table structure for table `user_meta`
--

CREATE TABLE `user_meta` (
  `user_id` bigint(20) NOT NULL COMMENT 'user id',
  `name` varchar(255) NOT NULL COMMENT 'meta name',
  `data` longtext DEFAULT NULL COMMENT 'metadata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='user metadata';

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `uuid` uuid NOT NULL COMMENT 'unique uuid session',
  `user_id` bigint(20) NOT NULL COMMENT 'user id',
  `auth_type` varchar(120) DEFAULT NULL COMMENT 'auth type (url/token/login)',
  `status` varchar(120) DEFAULT NULL COMMENT 'session status',
  `data` mediumtext DEFAULT NULL COMMENT 'session data',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'session created at',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'session update at',
  `expired_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='session active storage';

-- --------------------------------------------------------

--
-- Table structure for table `user_token_keys`
--

CREATE TABLE `user_token_keys` (
  `uuid` uuid NOT NULL DEFAULT uuid() COMMENT 'user token by uuid',
  `user_id` bigint(20) NOT NULL,
  `status` varchar(120) NOT NULL DEFAULT 'pending' COMMENT 'status (used/pending/expired)',
  `expired_at` datetime NOT NULL COMMENT 'expired at time',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'token created at',
  `used_at` datetime DEFAULT NULL COMMENT 'token used at'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='user token key for login url';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `unique_username_app_id` (`username`,`app_id`) USING BTREE,
  ADD UNIQUE KEY `index_status_time` (`status`,`app_id`,`created_at`,`updated_at`,`deleted_at`) USING BTREE,
  ADD KEY `index_app_id_status_username` (`app_id`,`status`,`username`) USING BTREE;

--
-- Indexes for table `account_meta`
--
ALTER TABLE `account_meta`
  ADD PRIMARY KEY (`account_id`,`name`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_user_id` (`user_id`),
  ADD KEY `index_type_user_id_time` (`type`,`user_id`,`created_at`),
  ADD KEY `index_name_type_user_id_time` (`name`,`type`,`user_id`,`created_at`) USING BTREE;

--
-- Indexes for table `apps`
--
ALTER TABLE `apps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`) USING BTREE,
  ADD KEY `index_name_status` (`name`,`status`) USING BTREE,
  ADD KEY `index_status` (`status`) USING BTREE,
  ADD KEY `apps_owner_users_id` (`owner`);

--
-- Indexes for table `app_meta`
--
ALTER TABLE `app_meta`
  ADD PRIMARY KEY (`app_id`,`name`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_level_time` (`level`,`created_at`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `unique_name` (`name`),
  ADD KEY `index_title_time` (`title`,`created_at`,`updated_at`),
  ADD KEY `roles_created_by_users_id` (`created_by`);

--
-- Indexes for table `role_access`
--
ALTER TABLE `role_access`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `unique_name` (`name`),
  ADD KEY `index_title_time` (`title`,`created_at`,`updated_at`),
  ADD KEY `role_access_created_by_users_id` (`created_by`);

--
-- Indexes for table `role_meta`
--
ALTER TABLE `role_meta`
  ADD PRIMARY KEY (`role_id`,`role_access_id`),
  ADD KEY `role_meta_role_access_role_access_id` (`role_access_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD KEY `members_role_role_name` (`role`),
  ADD KEY `index_search_compat` (`first_name`,`last_name`,`status`,`username`,`created_at`,`updated_at`,`deleted_at`),
  ADD KEY `index_status_time` (`status`,`created_at`,`updated_at`,`deleted_at`);

--
-- Indexes for table `user_meta`
--
ALTER TABLE `user_meta`
  ADD PRIMARY KEY (`user_id`,`name`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`uuid`),
  ADD KEY `index_user_id_auth_type_status` (`user_id`,`auth_type`,`status`) USING BTREE,
  ADD KEY `index_status` (`status`) USING BTREE,
  ADD KEY `index_auth_type_status` (`auth_type`,`status`) USING BTREE;

--
-- Indexes for table `user_token_keys`
--
ALTER TABLE `user_token_keys`
  ADD PRIMARY KEY (`uuid`),
  ADD KEY `index_status_user_id` (`status`,`user_id`),
  ADD KEY `index_user_id_status_time` (`user_id`,`status`,`created_at`,`expired_at`,`used_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'activity id';

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'log id';

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'user id';

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_app_id_apps_id` FOREIGN KEY (`app_id`) REFERENCES `apps` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `account_meta`
--
ALTER TABLE `account_meta`
  ADD CONSTRAINT `account_meta_account_id_accounts_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `apps`
--
ALTER TABLE `apps`
  ADD CONSTRAINT `apps_owner_users_id` FOREIGN KEY (`owner`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `app_meta`
--
ALTER TABLE `app_meta`
  ADD CONSTRAINT `app_meta_app_id_apps_id` FOREIGN KEY (`app_id`) REFERENCES `apps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_created_by_users_id` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `role_access`
--
ALTER TABLE `role_access`
  ADD CONSTRAINT `role_access_created_by_users_id` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `role_meta`
--
ALTER TABLE `role_meta`
  ADD CONSTRAINT `role_meta_role_access_role_access_id` FOREIGN KEY (`role_access_id`) REFERENCES `role_access` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `role_meta_role_id_roles_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `members_role_role_name` FOREIGN KEY (`role`) REFERENCES `roles` (`name`) ON UPDATE CASCADE;

--
-- Constraints for table `user_meta`
--
ALTER TABLE `user_meta`
  ADD CONSTRAINT `user_meta_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_token_keys`
--
ALTER TABLE `user_token_keys`
  ADD CONSTRAINT `user_token_keys_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
