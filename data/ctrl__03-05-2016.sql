# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.11-log)
# Database: ctrl
# Generation Time: 2016-05-03 08:48:12 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table ctrl_classes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ctrl_classes`;

CREATE TABLE `ctrl_classes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `table_name` varchar(255) DEFAULT '',
  `singular` varchar(255) DEFAULT NULL,
  `plural` varchar(255) DEFAULT '',
  `description` varchar(255) DEFAULT '',
  `permissions` set('list','add','edit','delete','view','copy','export','import','preview') DEFAULT NULL,
  `menu_title` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) DEFAULT '',
  `order` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

LOCK TABLES `ctrl_classes` WRITE;
/*!40000 ALTER TABLE `ctrl_classes` DISABLE KEYS */;

INSERT INTO `ctrl_classes` (`id`, `name`, `table_name`, `singular`, `plural`, `description`, `permissions`, `menu_title`, `icon`, `order`, `created_at`, `updated_at`)
VALUES
	(1,'Many','manies',NULL,'','',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(2,'One','ones',NULL,'','',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(3,'Pivot','pivots',NULL,'','',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(4,'Test','tests',NULL,'','',NULL,'Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(5,'User','users',NULL,'','',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57');

/*!40000 ALTER TABLE `ctrl_classes` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table ctrl_properties
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ctrl_properties`;

CREATE TABLE `ctrl_properties` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `ctrl_class_id` int(11) DEFAULT NULL,
  `related_to_id` int(11) DEFAULT NULL,
  `relationship_type` enum('belongsTo','hasMany','belongsToMany') DEFAULT NULL,
  `foreign_key` varchar(255) DEFAULT NULL,
  `local_key` varchar(255) DEFAULT NULL,
  `pivot_table` varchar(255) DEFAULT NULL,
  `flags` set('string','header','required','read_only','search','filtered_list','linked_list') DEFAULT NULL,
  `label` varchar(255) NOT NULL DEFAULT '',
  `field_type` enum('text','textarea','redactor','dropdown','checkbox','date','datetime','image','file','email','froala') DEFAULT NULL,
  `fieldset` varchar(255) NOT NULL DEFAULT '',
  `tip` text NOT NULL,
  `order` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

LOCK TABLES `ctrl_properties` WRITE;
/*!40000 ALTER TABLE `ctrl_properties` DISABLE KEYS */;

INSERT INTO `ctrl_properties` (`id`, `name`, `ctrl_class_id`, `related_to_id`, `relationship_type`, `foreign_key`, `local_key`, `pivot_table`, `flags`, `label`, `field_type`, `fieldset`, `tip`, `order`, `created_at`, `updated_at`)
VALUES
	(1,'title',1,NULL,NULL,NULL,NULL,NULL,'string,header,required,search','Title','text','Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(2,'title',2,NULL,NULL,NULL,NULL,NULL,'string,header,required,search','Title','text','Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(3,'title',3,NULL,NULL,NULL,NULL,NULL,'string,header,required,search','Title','text','Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(4,'title',4,NULL,NULL,NULL,NULL,NULL,'string,header,required,search','Title','text','Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(5,'content',4,NULL,NULL,NULL,NULL,NULL,NULL,'Content','froala','','',NULL,'2016-05-01 00:37:11','2016-04-30 21:43:57'),
	(6,'textarea',4,NULL,NULL,NULL,NULL,NULL,NULL,'Textarea','textarea','','',NULL,'2016-05-01 00:37:11','2016-04-30 21:43:57'),
	(7,'tick',4,NULL,NULL,NULL,NULL,NULL,NULL,'Tick','checkbox','','',NULL,'2016-05-01 00:37:12','2016-04-30 21:43:57'),
	(8,'file',4,NULL,NULL,NULL,NULL,NULL,NULL,'File','file','','',NULL,'2016-05-01 00:37:12','2016-04-30 21:43:57'),
	(9,'enum',4,NULL,NULL,NULL,NULL,NULL,NULL,'Enum','dropdown','','',NULL,'2016-05-01 00:37:12','2016-04-30 21:43:57'),
	(10,'date',4,NULL,NULL,NULL,NULL,NULL,NULL,'Date','date','Content','',NULL,'2016-05-01 00:37:18','2016-04-30 21:43:57'),
	(11,'datetime',4,NULL,NULL,NULL,NULL,NULL,NULL,'Datetime','datetime','Content','',NULL,'2016-05-01 01:26:17','2016-04-30 21:43:57'),
	(12,'image',4,NULL,NULL,NULL,NULL,NULL,NULL,'Image','image','','',NULL,'2016-04-30 22:44:21','2016-04-30 21:43:57'),
	(13,'email_address',4,NULL,NULL,NULL,NULL,NULL,NULL,'Email address','email','','',NULL,'2016-05-01 00:56:27','2016-04-30 21:43:57'),
	(14,'name',5,NULL,NULL,NULL,NULL,NULL,'string,header,required,search','Name','text','Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(15,'email',5,NULL,NULL,NULL,NULL,NULL,NULL,'Email','email','Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(16,'password',5,NULL,NULL,NULL,NULL,NULL,NULL,'Password','text','Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(17,'ctrl_group',5,NULL,NULL,NULL,NULL,NULL,NULL,'Ctrl group','text','Content','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(18,'test',1,4,'belongsTo','test_id','id',NULL,NULL,'',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(19,'many',4,1,'hasMany','test_id','id',NULL,NULL,'',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(20,'one',4,2,'belongsTo','one_id','id',NULL,'search','One','dropdown','','',NULL,'2016-05-02 17:11:49','2016-04-30 21:43:57'),
	(21,'test',2,4,'hasMany','one_id','id',NULL,NULL,'',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(22,'test',3,4,'belongsToMany','test_id','pivot_id','pivot_test',NULL,'',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57'),
	(23,'pivot',4,3,'belongsToMany','pivot_id','test_id','pivot_test',NULL,'',NULL,'','',NULL,'2016-04-30 21:43:57','2016-04-30 21:43:57');

/*!40000 ALTER TABLE `ctrl_properties` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table manies
# ------------------------------------------------------------

DROP TABLE IF EXISTS `manies`;

CREATE TABLE `manies` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `test_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `manies` WRITE;
/*!40000 ALTER TABLE `manies` DISABLE KEYS */;

INSERT INTO `manies` (`id`, `title`, `test_id`, `created_at`, `updated_at`)
VALUES
	(1,'Many 1',1,'2016-04-05 20:02:00','0000-00-00 00:00:00'),
	(2,'Many 2',1,'2016-04-05 20:02:00','0000-00-00 00:00:00');

/*!40000 ALTER TABLE `manies` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table migrations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;

INSERT INTO `migrations` (`migration`, `batch`)
VALUES
	('2014_10_12_000000_create_users_table',1),
	('2014_10_12_100000_create_password_resets_table',1);

/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table ones
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ones`;

CREATE TABLE `ones` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `ones` WRITE;
/*!40000 ALTER TABLE `ones` DISABLE KEYS */;

INSERT INTO `ones` (`id`, `title`, `created_at`, `updated_at`)
VALUES
	(1,'One','2016-04-05 11:26:00','0000-00-00 00:00:00');

/*!40000 ALTER TABLE `ones` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table password_resets
# ------------------------------------------------------------

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `password_resets_email_index` (`email`),
  KEY `password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table pivot_test
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pivot_test`;

CREATE TABLE `pivot_test` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pivot_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `pivot_test` WRITE;
/*!40000 ALTER TABLE `pivot_test` DISABLE KEYS */;

INSERT INTO `pivot_test` (`id`, `pivot_id`, `test_id`)
VALUES
	(1,1,1),
	(2,2,1),
	(3,1,2);

/*!40000 ALTER TABLE `pivot_test` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table pivots
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pivots`;

CREATE TABLE `pivots` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `pivots` WRITE;
/*!40000 ALTER TABLE `pivots` DISABLE KEYS */;

INSERT INTO `pivots` (`id`, `title`, `created_at`, `updated_at`)
VALUES
	(1,'Pivot 1','2016-04-05 19:16:42','0000-00-00 00:00:00'),
	(2,'Pivot 2','2016-04-05 19:16:44','0000-00-00 00:00:00'),
	(3,'Pivot 3','2016-04-05 19:16:46','0000-00-00 00:00:00');

/*!40000 ALTER TABLE `pivots` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table tests
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tests`;

CREATE TABLE `tests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `textarea` text NOT NULL,
  `tick` tinyint(4) NOT NULL,
  `file` varchar(255) NOT NULL,
  `enum` enum('One','Two','Three') DEFAULT NULL,
  `date` date DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `one_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `tests` WRITE;
/*!40000 ALTER TABLE `tests` DISABLE KEYS */;

INSERT INTO `tests` (`id`, `title`, `content`, `textarea`, `tick`, `file`, `enum`, `date`, `datetime`, `image`, `email_address`, `one_id`, `created_at`, `updated_at`, `deleted_at`)
VALUES
	(1,'testing','','',0,'/uploads/test-29992.txt',NULL,'0000-00-00','0000-00-00 00:00:00','','',0,'2016-05-01 00:12:19','2016-04-30 23:12:19',NULL),
	(2,'Another test title','','',0,'',NULL,'0000-00-00','0000-00-00 00:00:00','','',1,'2016-04-13 12:53:23','2016-04-08 16:19:11',NULL),
	(3,'A new title!','<p>Image:&nbsp;</p><p><img class=\"fr-dib fr-draggable fr-fil\" src=\"/uploads/image_5722ea9ede824.jpg\" style=\"width: 168px; height: 112px;\"></p><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus tristique ac leo quis convallis. Maecenas turpis ex, blandit id aliquet ac, molestie sed risus. In vel lorem nec libero elementum mollis. Maecenas eget ultricies risus, vitae porttitor augue. Maecenas porta euismod lectus, ut dignissim nisi porttitor semper. Nam vehicula tellus lacus, sed euismod felis lobortis vitae. Integer efficitur, mauris non fermentum congue, lacus turpis scelerisque velit, vitae posuere massa urna at sem. Nunc vel scelerisque velit. Nam vitae tristique eros, elementum posuere diam. Donec nec libero eu nibh elementum ullamcorper. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p><p>Duis luctus interdum dui, eu euismod mauris luctus nec. Aliquam convallis turpis in metus lobortis lacinia. Nullam blandit finibus ipsum nec congue. Morbi non lobortis dolor. Nam pellentesque, diam non interdum bibendum, nisi eros molestie neque, vestibulum suscipit justo nulla ac ante. Aliquam id dignissim lectus. Maecenas faucibus felis at lacinia maximus. Cras tempor imperdiet lacinia. Ut auctor sem aliquam, consectetur nisi quis, ullamcorper justo. Vivamus et nulla blandit, tincidunt turpis in, blandit nisl. Nulla quis sagittis arcu. Etiam dapibus mauris nec dolor venenatis, sed semper velit varius. Etiam nec turpis quam. Vestibulum non velit lorem. Vestibulum eleifend mattis velit, non pellentesque nunc vehicula a.</p>','',0,'',NULL,'2006-09-24','2016-05-13 08:00:00','/uploads/image_57251c82f41b6.png','',0,'2016-05-01 01:26:31','2016-05-01 00:26:31',NULL),
	(4,'asdsd!!!','','',0,'',NULL,'0000-00-00','0000-00-00 00:00:00','','',0,'2016-04-08 17:19:08','2016-04-08 16:19:08',NULL),
	(5,'another new test records 555','','',0,'',NULL,'0000-00-00','0000-00-00 00:00:00','','',0,'2016-04-08 17:19:08','2016-04-08 16:19:08',NULL);

/*!40000 ALTER TABLE `tests` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ctrl_group` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `remember_token`, `ctrl_group`, `created_at`, `updated_at`)
VALUES
	(1,'Chris Gibson','chris@phoenixdigital.agency','$2y$10$vM40/hHkYiaRAfCDJoFVCesP1uUaSEKy9WbDqQtsU4pBWV84aoX1S','o1wzcsplAT0PJTTfdcpieUmUf4rV2fPaoLWmqRmjRHTMAJTVAZAHekL4RafI','root',NULL,'2016-04-05 10:12:15');

/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
