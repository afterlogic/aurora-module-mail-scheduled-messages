CREATE TABLE IF NOT EXISTS `%PREFIX%mail_scheduled_messages` (
    `id` int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `account_id` BIGINT(64) NOT NULL,
    `folder_full_name` TEXT,
    `message_uid` varchar(255) NOT NULL,
    `schedule_timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
