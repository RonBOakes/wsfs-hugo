-- MySQL dump 10.19  Distrib 10.3.35-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: hugo
-- ------------------------------------------------------
-- Server version	10.3.35-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `award_categories`
--

DROP TABLE IF EXISTS `award_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `award_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(45) NOT NULL,
  `category_description` text NOT NULL COMMENT ' ',
  `include_description_on_vote` tinyint(1) NOT NULL,
  `ballot_position` smallint(6) NOT NULL COMMENT 'Position on the ballot (order)',
  `primary_datum_description` varchar(45) NOT NULL,
  `datum_2_description` varchar(45) DEFAULT NULL,
  `datum_3_description` varchar(45) DEFAULT NULL,
  `personal_category` tinyint(1) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name_UNIQUE` (`category_name`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_log`
--

DROP TABLE IF EXISTS `email_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_log` (
  `email_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `nominator_pin` varchar(128) NOT NULL,
  `send_time` datetime NOT NULL,
  `send_result` varchar(32) NOT NULL,
  `email_text` longtext NOT NULL,
  `email_address` varchar(64) NOT NULL,
  `server_ip` varchar(32) NOT NULL,
  PRIMARY KEY (`email_log_id`)
) ENGINE=MyISAM AUTO_INCREMENT=53302 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hugo_ballot_counts`
--

DROP TABLE IF EXISTS `hugo_ballot_counts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hugo_ballot_counts` (
  `shortlist_id` int(8) NOT NULL,
  `placement` int(8) unsigned NOT NULL COMMENT '1 for winner, 2 for second, etc',
  `round` int(8) unsigned NOT NULL,
  `count` int(8) unsigned NOT NULL,
  PRIMARY KEY (`shortlist_id`,`placement`,`round`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hugo_ballot_entry`
--

DROP TABLE IF EXISTS `hugo_ballot_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hugo_ballot_entry` (
  `ballot_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` varchar(32) NOT NULL,
  `category_id` int(11) NOT NULL,
  `short_list_id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `ballot_approved` tinyint(1) NOT NULL,
  `ballot_deleted` tinyint(1) NOT NULL,
  `unverified_voter` tinyint(1) NOT NULL,
  `ip_added_from` varchar(45) NOT NULL,
  `time_added` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ballot_entry_id`)
) ENGINE=MyISAM AUTO_INCREMENT=123542 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hugo_shortlist`
--

DROP TABLE IF EXISTS `hugo_shortlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hugo_shortlist` (
  `shortlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT 'reference to award_categories',
  `datum_1` varchar(512) NOT NULL,
  `sort_value` varchar(512) NOT NULL,
  `datum_2` varchar(512) DEFAULT NULL,
  `datum_3` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`shortlist_id`)
) ENGINE=MyISAM AUTO_INCREMENT=116 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nomination_configuration`
--

DROP TABLE IF EXISTS `nomination_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nomination_configuration` (
  `config_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `nomination_open` datetime NOT NULL COMMENT 'Date and time (local to db server) when nominations open',
  `nomination_close` datetime NOT NULL COMMENT 'Date and time (local to db server) when nominations close',
  `preview_ends` datetime NOT NULL,
  `eligibility_text_blob` text NOT NULL,
  `update_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`config_entry_id`),
  UNIQUE KEY `config_entry_id` (`config_entry_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nomination_page_log`
--

DROP TABLE IF EXISTS `nomination_page_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nomination_page_log` (
  `nomination_page_log_id` int(10) NOT NULL AUTO_INCREMENT,
  `nomination_page` longtext NOT NULL,
  `ip_received_from` varchar(20) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`nomination_page_log_id`)
) ENGINE=MyISAM AUTO_INCREMENT=70566 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nomination_pin_email_info`
--

DROP TABLE IF EXISTS `nomination_pin_email_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nomination_pin_email_info` (
  `pin_email_info_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(45) NOT NULL,
  `second_name` varchar(45) NOT NULL,
  `member_id` varchar(45) NOT NULL,
  `pin` varchar(15) NOT NULL,
  `email` varchar(45) NOT NULL,
  `source` enum('PRIOR','CURRENT','NEXT') NOT NULL,
  `initial_mail_sent` tinyint(1) NOT NULL DEFAULT 0,
  `second_mail_sent` tinyint(1) NOT NULL DEFAULT 0,
  `third_mail_sent` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`pin_email_info_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3796 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nomination_pin_email_info_removed`
--

DROP TABLE IF EXISTS `nomination_pin_email_info_removed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nomination_pin_email_info_removed` (
  `pin_email_info_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(45) NOT NULL,
  `second_name` varchar(45) NOT NULL,
  `member_id` varchar(45) NOT NULL,
  `pin` varchar(15) NOT NULL,
  `email` varchar(45) NOT NULL,
  `source` enum('PRIOR','CURRENT','NEXT') NOT NULL,
  `initial_mail_sent` tinyint(1) NOT NULL DEFAULT 0,
  `second_mail_sent` tinyint(1) NOT NULL DEFAULT 0,
  `third_mail_sent` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`pin_email_info_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nomination_post_summary`
--

DROP TABLE IF EXISTS `nomination_post_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nomination_post_summary` (
  `nominaton_post_key` int(11) NOT NULL AUTO_INCREMENT,
  `post_contents` text NOT NULL,
  `server_contents` text NOT NULL,
  PRIMARY KEY (`nominaton_post_key`)
) ENGINE=MyISAM AUTO_INCREMENT=70922 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nominations`
--

DROP TABLE IF EXISTS `nominations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nominations` (
  `nomination_id` int(11) NOT NULL AUTO_INCREMENT,
  `nominator_id` varchar(32) NOT NULL,
  `award_category_id` int(11) NOT NULL,
  `nominee_id` int(11) DEFAULT NULL,
  `primary_datum` varchar(256) NOT NULL,
  `datum_2` varchar(256) DEFAULT NULL,
  `datum_3` varchar(256) DEFAULT NULL,
  `nomination_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_added_from` varchar(45) NOT NULL,
  `nomination_approved` tinyint(1) NOT NULL DEFAULT 0,
  `nomination_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `unverified_nominator` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`nomination_id`)
) ENGINE=MyISAM AUTO_INCREMENT=859593 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nominee`
--

DROP TABLE IF EXISTS `nominee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nominee` (
  `nominee_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `primary_datum` varchar(256) NOT NULL,
  `datum_2` varchar(256) NOT NULL,
  `datum_3` varchar(256) NOT NULL,
  PRIMARY KEY (`nominee_id`)
) ENGINE=MyISAM AUTO_INCREMENT=21632 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `packet_download_log`
--

DROP TABLE IF EXISTS `packet_download_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packet_download_log` (
  `dowload_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` varchar(32) NOT NULL,
  `packet_file_id` int(11) NOT NULL,
  `download_complete` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `download_ip` varchar(20) NOT NULL,
  `user_agent` varchar(128) NOT NULL,
  PRIMARY KEY (`dowload_id`)
) ENGINE=InnoDB AUTO_INCREMENT=68747 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `packet_files`
--

DROP TABLE IF EXISTS `packet_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packet_files` (
  `packet_file_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_position` int(8) NOT NULL,
  `show_on_packet_page` int(1) NOT NULL,
  `file_short_description` varchar(120) NOT NULL,
  `file_download_name` varchar(45) NOT NULL,
  `file_format_notes` text DEFAULT NULL,
  `file_size` decimal(4,1) NOT NULL,
  `sha256sum` varchar(80) NOT NULL,
  PRIMARY KEY (`packet_file_id`),
  UNIQUE KEY `file_download_name` (`file_download_name`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `provisional_nominations`
--

DROP TABLE IF EXISTS `provisional_nominations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provisional_nominations` (
  `nomination_id` int(11) NOT NULL AUTO_INCREMENT,
  `nominator_id` int(11) NOT NULL,
  `award_category_id` int(11) NOT NULL,
  `primary_datum` varchar(256) NOT NULL,
  `datum_2` varchar(256) DEFAULT NULL,
  `datum_3` varchar(256) DEFAULT NULL,
  `nomination_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `nomination_approved` smallint(6) NOT NULL DEFAULT 0,
  `nomination_deleted` smallint(6) NOT NULL DEFAULT 0,
  `unverified_nominator` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`nomination_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `provisional_nominator`
--

DROP TABLE IF EXISTS `provisional_nominator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provisional_nominator` (
  `provisional_nominator_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(80) DEFAULT NULL,
  `second_name` varchar(80) NOT NULL,
  `membership_number` varchar(10) NOT NULL,
  `email` varchar(80) DEFAULT NULL,
  `pin` varchar(10) NOT NULL,
  PRIMARY KEY (`provisional_nominator_id`),
  UNIQUE KEY `provisional_nominator_id` (`provisional_nominator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `voting_configuration`
--

DROP TABLE IF EXISTS `voting_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voting_configuration` (
  `config_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `voting_open` datetime NOT NULL COMMENT 'Date and time (local to db server) when nominations open',
  `voting_close` datetime NOT NULL COMMENT 'Date and time (local to db server) when nominations close',
  `preview_ends` datetime NOT NULL,
  `packet_opens` datetime DEFAULT NULL,
  `packet_preview_ends` datetime DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`config_entry_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-12-24 13:35:51
