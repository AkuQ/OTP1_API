SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `chat`;
CREATE TABLE `chat` (
	`chat_id` bigint NOT NULL AUTO_INCREMENT,
	`name` varchar(32) NOT NULL,
	`password` varchar(256) NOT NULL,
	`created` DATETIME NOT NULL,
	`updated` DATETIME NOT NULL,
	PRIMARY KEY (`chat_id`)
);

DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
	`chat_id` bigint NOT NULL,
	`message_id` bigint NOT NULL AUTO_INCREMENT,
	`user_id` bigint NOT NULL,
	`content` varchar(256) NOT NULL,
	`created` DATETIME NOT NULL,
	PRIMARY KEY (`message_id`)
);

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
	`user_id` bigint NOT NULL AUTO_INCREMENT,
	`name` varchar(32) NOT NULL,
	`token` varchar(256) NOT NULL,
	`chat_id` bigint,
	`created` DATETIME NOT NULL,
	`updated` DATETIME NOT NULL,
	`is_online` TINYINT DEFAULT 0,
	PRIMARY KEY (`user_id`)
);

DROP TABLE IF EXISTS `workspace`;
CREATE TABLE `workspace` (
	`chat_id` bigint NOT NULL,
	`content` longtext NOT NULL DEFAULT '',
	PRIMARY KEY (`chat_id`)
);

DROP TABLE IF EXISTS `workspace_updates`;
CREATE TABLE `workspace_updates` (
	`update_id` bigint NOT NULL AUTO_INCREMENT,
	`chat_id` bigint NOT NULL,
	`user_id` bigint NOT NULL,
	`pos` int NOT NULL,
	`mode` tinyint NOT NULL,
	`input` varchar(64),
	PRIMARY KEY (`update_id`)
);

ALTER TABLE `message` ADD CONSTRAINT `message_fk0` FOREIGN KEY (`chat_id`) REFERENCES `chat`(`chat_id`);

ALTER TABLE `message` ADD CONSTRAINT `message_fk1` FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`);

ALTER TABLE `user` ADD CONSTRAINT `user_fk0` FOREIGN KEY (`chat_id`) REFERENCES `chat`(`chat_id`);

ALTER TABLE `workspace` ADD CONSTRAINT `workspace_fk0` FOREIGN KEY (`chat_id`) REFERENCES `chat`(`chat_id`);

ALTER TABLE `workspace_updates` ADD CONSTRAINT `workspace_updates_fk0` FOREIGN KEY (`chat_id`) REFERENCES `chat`(`chat_id`);

ALTER TABLE `workspace_updates` ADD CONSTRAINT `workspace_updates_fk1` FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`);

SET FOREIGN_KEY_CHECKS = 1;
