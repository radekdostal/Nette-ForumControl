-- Adminer 3.3.2 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE `forum_control` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_czech_ci */;
USE `forum_control`;

CREATE TABLE `forum` (
  `id_forum` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Forum ID',
  `forum` varchar(100) COLLATE utf8_czech_ci NOT NULL COMMENT 'Forum name',
  PRIMARY KEY (`id_forum`),
  UNIQUE KEY `nazev` (`forum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Forum';

INSERT INTO `forum` (`id_forum`, `forum`) VALUES
(1,	'Test forum');

CREATE TABLE `forum_threads` (
  `id_thread` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Thread ID',
  `id_forum` int(10) unsigned NOT NULL COMMENT 'Forum ID',
  `sequence` int(10) unsigned NOT NULL COMMENT 'Order within forum',
  `depth` int(10) unsigned NOT NULL COMMENT 'Depth within forum',
  `name` varchar(40) COLLATE utf8_czech_ci NOT NULL COMMENT 'Name',
  `ip` varchar(39) COLLATE utf8_czech_ci NOT NULL COMMENT 'IP address',
  `title` varchar(100) COLLATE utf8_czech_ci NOT NULL COMMENT 'Title',
  `date_time` datetime NOT NULL COMMENT 'Date and time of the topic',
  `topic` text COLLATE utf8_czech_ci NOT NULL COMMENT 'Topic',
  PRIMARY KEY (`id_thread`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Threads';

INSERT INTO `forum_threads` (`id_thread`, `id_forum`, `sequence`, `depth`, `name`, `ip`, `title`, `date_time`, `topic`) VALUES
(1,	1,	1,	0,	'Radek Dostál',	'127.0.0.1',	'First topic',	'2011-04-11 11:32:44',	'Lorem ipsum dolor sit amet consectetuer semper ut Integer justo tellus. Nam ridiculus convallis parturient tellus Duis id condimentum porta sapien quis. Dictumst in Integer et nisl et amet volutpat diam ac Phasellus. Orci amet augue sed interdum porttitor at dis justo pede id. Non condimentum et consectetuer libero et purus risus dolor sem enim. Cras eu Pellentesque consectetuer congue et vitae ridiculus.'),
(2,	1,	4,	0,	'John Doe',	'127.0.0.1',	'Second topic',	'2011-04-11 11:33:25',	'Wisi mauris eget est consequat Vestibulum tristique Fusce nisl Fusce Sed. Nec condimentum odio mus et condimentum turpis Suspendisse dictumst tellus vel. Elit orci pede elit laoreet malesuada dignissim eget Nullam est vitae. Consectetuer vitae sapien semper Sed et lacinia sem Nam magna Nam. Sem lacinia nibh nibh natoque enim amet egestas Mauris wisi convallis. Eleifend nibh pretium Vestibulum montes netus Aenean felis euismod consectetuer sed. Nibh.'),
(3,	1,	2,	1,	'Anonymous',	'127.0.0.1',	'Re: First topic',	'2011-04-11 11:33:58',	'Lorem vel dolor vel volutpat congue facilisi leo semper lorem cursus. Feugiat id Curabitur Cum tincidunt Pellentesque ipsum tincidunt enim risus ut. Lobortis nibh fermentum nibh semper et amet lacus leo congue sollicitudin. Tincidunt quis quis nunc arcu ante venenatis Nulla convallis lobortis sem. Congue vel Morbi mauris consequat nulla sit Vivamus.'),
(4,	1,	3,	2,	'Anonymous',	'127.0.0.1',	'Re: First topic',	'2011-04-11 11:39:13',	'Justo at vitae in sed vitae sit wisi tellus massa enim. Nunc Pellentesque Curabitur porta Aliquam Vivamus felis Donec nibh ipsum laoreet. Pede sapien mollis urna sit id Curabitur turpis Maecenas penatibus lobortis. Tincidunt nisl congue et sed Vivamus Suspendisse risus pretium pede Phasellus. Mauris hendrerit tortor nunc augue nisl Nunc at.');

-- 2011-08-12 11:29:52
