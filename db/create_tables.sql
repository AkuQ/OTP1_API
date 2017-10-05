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
	PRIMARY KEY (`user_id`)
);

DROP TABLE IF EXISTS `workspace`;
CREATE TABLE `workspace` (
	`workspace_id` bigint NOT NULL AUTO_INCREMENT,
	`chat_id` bigint NOT NULL,
	PRIMARY KEY (`workspace_id`)
);

DROP TABLE IF EXISTS `workspace_line`;
CREATE TABLE `workspace_line` (
	`line_id` bigint NOT NULL,
	`workspace_id` bigint NOT NULL,
	`content` varchar(256) NOT NULL,
	`line_no` bigint NOT NULL,
	PRIMARY KEY (`line_id`,`workspace_id`)
);

DROP TABLE IF EXISTS `line_lock`;
CREATE TABLE `line_lock` (
	`user_id` bigint NOT NULL,
	`workspace_id` bigint NOT NULL,
	`line_id` bigint NOT NULL,
	`acquired` TIMESTAMP NOT NULL,
	PRIMARY KEY (`user_id`,`workspace_id`)
);

ALTER TABLE `message` ADD CONSTRAINT `message_fk0` FOREIGN KEY (`chat_id`) REFERENCES `chat`(`chat_id`);

ALTER TABLE `message` ADD CONSTRAINT `message_fk1` FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`);

ALTER TABLE `user` ADD CONSTRAINT `user_fk0` FOREIGN KEY (`chat_id`) REFERENCES `chat`(`chat_id`);

ALTER TABLE `workspace` ADD CONSTRAINT `workspace_fk0` FOREIGN KEY (`chat_id`) REFERENCES `chat`(`chat_id`);

ALTER TABLE `workspace_line` ADD CONSTRAINT `workspace_line_fk0` FOREIGN KEY (`workspace_id`) REFERENCES `workspace`(`workspace_id`);

ALTER TABLE `line_lock` ADD CONSTRAINT `line_lock_fk0` FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`);

ALTER TABLE `line_lock` ADD CONSTRAINT `line_lock_fk1` FOREIGN KEY (`workspace_id`) REFERENCES `workspace`(`workspace_id`);

ALTER TABLE `line_lock` ADD CONSTRAINT `line_lock_fk2` FOREIGN KEY (`line_id`) REFERENCES `workspace_line`(`line_id`);

SET FOREIGN_KEY_CHECKS = 1;