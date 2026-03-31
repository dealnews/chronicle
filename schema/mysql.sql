CREATE TABLE `sources` (
    `source_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`source_id`),
    UNIQUE KEY `name` (`name`)
);

CREATE TABLE `types` (
    `type_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `source_id` bigint unsigned NOT NULL,
    `name` varchar(255) NOT NULL,
    `plugin` varchar(100) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`type_id`),
    UNIQUE KEY `type_name` (`source_id`,`name`)
);

CREATE TABLE `sessions` (
    `session_id` varchar(128) NOT NULL,
    `data`       longtext,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`session_id`),
    KEY `updated_at` (`updated_at`)
);

CREATE TABLE `api_keys` (
    `api_key_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name`       varchar(255) NOT NULL,
    `key_hash`   varchar(64) NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revoked_at` datetime DEFAULT NULL,
    PRIMARY KEY (`api_key_id`),
    UNIQUE KEY `key_hash` (`key_hash`)
);

CREATE TABLE `users` (
    `user_id`       bigint unsigned NOT NULL AUTO_INCREMENT,
    `email`         varchar(255) NOT NULL,
    `name`          varchar(255) DEFAULT NULL,
    `password_hash` varchar(255) DEFAULT NULL,
    `google_id`     varchar(255) DEFAULT NULL,
    `created_at`    datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login_at` datetime DEFAULT NULL,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `google_id` (`google_id`)
);

CREATE TABLE `logs` (
    `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `type_id` bigint unsigned NOT NULL,
    `action` enum('create','update','delete') NOT NULL DEFAULT 'create',
    `object_id` varchar(255) NOT NULL,
    `version` varchar(255) DEFAULT NULL,
    `data` longtext,
    `change_date` datetime NOT NULL,
    `updated_by` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `object_diffs` (`object_id`,`change_date`)
);
