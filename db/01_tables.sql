
-- -----------------------------------------------
-- Create list items table
-- -----------------------------------------------

DROP TABLE IF EXISTS `todo_list_items`;
CREATE TABLE `todo_list_items` (
  `item_id` int unsigned NOT NULL PRIMARY KEY,
  `bc_updated` datetime NULL DEFAULT NULL,
  `my_updated` datetime NOT NULL,
  `deleted` tinyint(1) NULL DEFAULT NULL,
  UNIQUE `item_id` (`item_id`)
) ENGINE = 'InnoDB' COLLATE 'utf8_general_ci';


-- -----------------------------------------------
-- Create messages table
-- -----------------------------------------------

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `message_id` int unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
	UNIQUE `unique` (`item_id`, `message_id`),
	PRIMARY KEY `message_id` (`message_id`),
  INDEX `item_id` (`item_id`)
) ENGINE = 'InnoDB' COLLATE 'utf8_general_ci';