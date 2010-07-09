--
-- table structure. i'm using utf8_general_ci collation.
--

CREATE TABLE IF NOT EXISTS `realid_posts` (
	`id` int(10) unsigned NOT NULL,
	`post_date_gmt` datetime default NULL,
	`deleted` tinyint(4) NOT NULL default '0',
	`page` int(10) unsigned NOT NULL,
	`character` varchar(50) NOT NULL,
	`level` tinyint(3) unsigned default NULL,
	`guild` varchar(50) NOT NULL,
	`realm` varchar(50) NOT NULL,
	`region` enum('us','de','uk','ru','es','fr') NOT NULL default 'us',
	`scanned` datetime NOT NULL,
	`rescanned` datetime NOT NULL,
	UNIQUE KEY `id` (`id`,`region`),
	KEY `page` (`page`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
