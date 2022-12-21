-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: mac2-db02.midamericon2.org:3306
-- Generation Time: Aug 22, 2016 at 03:43 PM
-- Server version: 5.6.23-log
-- PHP Version: 5.5.9-1ubuntu4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `hugo`
--
CREATE DATABASE IF NOT EXISTS `hugo` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `hugo`;

-- --------------------------------------------------------

--
-- Table structure for table `award_categories`
--

DROP TABLE IF EXISTS `award_categories`;
CREATE TABLE IF NOT EXISTS `award_categories` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

DROP TABLE IF EXISTS `email_log`;
CREATE TABLE IF NOT EXISTS `email_log` (
  `email_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `nominator_pin` varchar(128) NOT NULL,
  `send_time` datetime NOT NULL,
  `send_result` varchar(32) NOT NULL,
  `email_text` longtext NOT NULL,
  `email_address` varchar(64) NOT NULL,
  `server_ip` varchar(32) NOT NULL,
  PRIMARY KEY (`email_log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=53302 ;

-- --------------------------------------------------------

--
-- Table structure for table `hugo_ballot_counts`
--

DROP TABLE IF EXISTS `hugo_ballot_counts`;
CREATE TABLE IF NOT EXISTS `hugo_ballot_counts` (
  `shortlist_id` int(8) NOT NULL,
  `placement` int(8) unsigned NOT NULL COMMENT '1 for winner, 2 for second, etc',
  `round` int(8) unsigned NOT NULL,
  `count` int(8) unsigned NOT NULL,
  PRIMARY KEY (`shortlist_id`,`placement`,`round`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `hugo_ballot_entry`
--

DROP TABLE IF EXISTS `hugo_ballot_entry`;
CREATE TABLE IF NOT EXISTS `hugo_ballot_entry` (
  `ballot_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` varchar(32) NOT NULL,
  `category_id` int(11) NOT NULL,
  `short_list_id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `ballot_approved` tinyint(1) NOT NULL,
  `ballot_deleted` tinyint(1) NOT NULL,
  `unverified_voter` tinyint(1) NOT NULL,
  `ip_added_from` varchar(45) NOT NULL,
  `time_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ballot_entry_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=123542 ;

-- --------------------------------------------------------

--
-- Table structure for table `hugo_shortlist`
--

DROP TABLE IF EXISTS `hugo_shortlist`;
CREATE TABLE IF NOT EXISTS `hugo_shortlist` (
  `shortlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT 'reference to award_categories',
  `datum_1` varchar(512) NOT NULL,
  `sort_value` varchar(512) NOT NULL,
  `datum_2` varchar(512) DEFAULT NULL,
  `datum_3` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`shortlist_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=116 ;

-- --------------------------------------------------------

--
-- Table structure for table `nominations`
--

DROP TABLE IF EXISTS `nominations`;
CREATE TABLE IF NOT EXISTS `nominations` (
  `nomination_id` int(11) NOT NULL AUTO_INCREMENT,
  `nominator_id` varchar(32) NOT NULL,
  `award_category_id` int(11) NOT NULL,
  `nominee_id` int(11) DEFAULT NULL,
  `primary_datum` varchar(256) NOT NULL,
  `datum_2` varchar(256) DEFAULT NULL,
  `datum_3` varchar(256) DEFAULT NULL,
  `nomination_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_added_from` varchar(45) NOT NULL,
  `nomination_approved` tinyint(1) NOT NULL DEFAULT '0',
  `nomination_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `unverified_nominator` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nomination_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=859593 ;

-- --------------------------------------------------------

--
-- Table structure for table `nomination_configuration`
--

DROP TABLE IF EXISTS `nomination_configuration`;
CREATE TABLE IF NOT EXISTS `nomination_configuration` (
  `config_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `nomination_open` datetime NOT NULL COMMENT 'Date and time (local to db server) when nominations open',
  `nomination_close` datetime NOT NULL COMMENT 'Date and time (local to db server) when nominations close',
  `preview_ends` datetime NOT NULL,
  `eligibility_text_blob` text NOT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_entry_id`),
  UNIQUE KEY `config_entry_id` (`config_entry_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `nomination_page_log`
--

DROP TABLE IF EXISTS `nomination_page_log`;
CREATE TABLE IF NOT EXISTS `nomination_page_log` (
  `nomination_page_log_id` int(10) NOT NULL AUTO_INCREMENT,
  `nomination_page` longtext NOT NULL,
  `ip_received_from` varchar(20) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`nomination_page_log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=70566 ;

-- --------------------------------------------------------

--
-- Table structure for table `nomination_pin_email_info`
--

DROP TABLE IF EXISTS `nomination_pin_email_info`;
CREATE TABLE IF NOT EXISTS `nomination_pin_email_info` (
  `pin_email_info_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(45) NOT NULL,
  `second_name` varchar(45) NOT NULL,
  `member_id` varchar(45) NOT NULL,
  `pin` varchar(15) NOT NULL,
  `email` varchar(45) NOT NULL,
  `source` enum('PRIOR','CURRENT','NEXT') NOT NULL,
  `initial_mail_sent` tinyint(1) NOT NULL DEFAULT '0',
  `second_mail_sent` tinyint(1) NOT NULL DEFAULT '0',
  `third_mail_sent` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pin_email_info_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3796 ;

-- --------------------------------------------------------

--
-- Table structure for table `nomination_pin_email_info_removed`
--

DROP TABLE IF EXISTS `nomination_pin_email_info_removed`;
CREATE TABLE IF NOT EXISTS `nomination_pin_email_info_removed` (
  `pin_email_info_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(45) NOT NULL,
  `second_name` varchar(45) NOT NULL,
  `member_id` varchar(45) NOT NULL,
  `pin` varchar(15) NOT NULL,
  `email` varchar(45) NOT NULL,
  `source` enum('PRIOR','CURRENT','NEXT') NOT NULL,
  `initial_mail_sent` tinyint(1) NOT NULL DEFAULT '0',
  `second_mail_sent` tinyint(1) NOT NULL DEFAULT '0',
  `third_mail_sent` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pin_email_info_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nomination_post_summary`
--

DROP TABLE IF EXISTS `nomination_post_summary`;
CREATE TABLE IF NOT EXISTS `nomination_post_summary` (
  `nominaton_post_key` int(11) NOT NULL AUTO_INCREMENT,
  `post_contents` text NOT NULL,
  `server_contents` text NOT NULL,
  PRIMARY KEY (`nominaton_post_key`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=70922 ;

-- --------------------------------------------------------

--
-- Table structure for table `nominee`
--

DROP TABLE IF EXISTS `nominee`;
CREATE TABLE IF NOT EXISTS `nominee` (
  `nominee_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `primary_datum` varchar(256) NOT NULL,
  `datum_2` varchar(256) NOT NULL,
  `datum_3` varchar(256) NOT NULL,
  PRIMARY KEY (`nominee_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=21632 ;

-- --------------------------------------------------------

--
-- Table structure for table `packet_download_log`
--

DROP TABLE IF EXISTS `packet_download_log`;
CREATE TABLE IF NOT EXISTS `packet_download_log` (
  `dowload_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` varchar(32) NOT NULL,
  `packet_file_id` int(11) NOT NULL,
  `download_complete` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `download_ip` varchar(20) NOT NULL,
  `user_agent` varchar(128) NOT NULL,
  PRIMARY KEY (`dowload_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=68747 ;

-- --------------------------------------------------------

--
-- Table structure for table `packet_files`
--

DROP TABLE IF EXISTS `packet_files`;
CREATE TABLE IF NOT EXISTS `packet_files` (
  `packet_file_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_position` int(8) NOT NULL,
  `show_on_packet_page` int(1) NOT NULL,
  `file_short_description` varchar(120) NOT NULL,
  `file_download_name` varchar(45) NOT NULL,
  `file_format_notes` text,
  `file_size` decimal(4,1) NOT NULL,
  `sha256sum` varchar(80) NOT NULL,
  PRIMARY KEY (`packet_file_id`),
  UNIQUE KEY `file_download_name` (`file_download_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Table structure for table `provisional_nominations`
--

DROP TABLE IF EXISTS `provisional_nominations`;
CREATE TABLE IF NOT EXISTS `provisional_nominations` (
  `nomination_id` int(11) NOT NULL AUTO_INCREMENT,
  `nominator_id` int(11) NOT NULL,
  `award_category_id` int(11) NOT NULL,
  `primary_datum` varchar(256) NOT NULL,
  `datum_2` varchar(256) DEFAULT NULL,
  `datum_3` varchar(256) DEFAULT NULL,
  `nomination_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nomination_approved` smallint(6) NOT NULL DEFAULT '0',
  `nomination_deleted` smallint(6) NOT NULL DEFAULT '0',
  `unverified_nominator` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nomination_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `provisional_nominator`
--

DROP TABLE IF EXISTS `provisional_nominator`;
CREATE TABLE IF NOT EXISTS `provisional_nominator` (
  `provisional_nominator_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(80) DEFAULT NULL,
  `second_name` varchar(80) NOT NULL,
  `membership_number` varchar(10) NOT NULL,
  `email` varchar(80) DEFAULT NULL,
  `pin` varchar(10) NOT NULL,
  PRIMARY KEY (`provisional_nominator_id`),
  UNIQUE KEY `provisional_nominator_id` (`provisional_nominator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `voting_configuration`
--

DROP TABLE IF EXISTS `voting_configuration`;
CREATE TABLE IF NOT EXISTS `voting_configuration` (
  `config_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `voting_open` datetime NOT NULL COMMENT 'Date and time (local to db server) when nominations open',
  `voting_close` datetime NOT NULL COMMENT 'Date and time (local to db server) when nominations close',
  `preview_ends` datetime NOT NULL,
  `packet_opens` datetime DEFAULT NULL,
  `packet_preview_ends` datetime DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_entry_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
