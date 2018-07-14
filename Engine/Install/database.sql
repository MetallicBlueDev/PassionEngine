-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2018 at 07:35 PM
-- Server version: 5.7.17
-- PHP Version: 7.1.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trancers_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tr_banned`
--

CREATE TABLE `tr_banned` (
  `ban_id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(50) NOT NULL,
  `name` varchar(45) NOT NULL,
  `mail` varchar(80) DEFAULT NULL,
  `reason` text NOT NULL,
  `banishment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` tinyint(1) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_blocks`
--

CREATE TABLE `tr_blocks` (
  `block_id` int(10) UNSIGNED NOT NULL,
  `side` tinyint(1) UNSIGNED NOT NULL,
  `position` tinyint(2) UNSIGNED NOT NULL,
  `title` varchar(45) NOT NULL,
  `type` varchar(45) NOT NULL,
  `rank` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `allMods` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_blocks_configs`
--

CREATE TABLE `tr_blocks_configs` (
  `bconfig_id` int(10) UNSIGNED NOT NULL,
  `block_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_blocks_visibility`
--

CREATE TABLE `tr_blocks_visibility` (
  `bvisibility_id` int(10) UNSIGNED NOT NULL,
  `block_id` int(10) UNSIGNED NOT NULL,
  `mod_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_configs`
--

CREATE TABLE `tr_configs` (
  `config_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_menus`
--

CREATE TABLE `tr_menus` (
  `menu_id` int(10) UNSIGNED NOT NULL,
  `block_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `content` text,
  `sublevel` smallint(10) UNSIGNED NOT NULL DEFAULT '0',
  `position` smallint(10) UNSIGNED NOT NULL DEFAULT '0',
  `rank` tinyint(1) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_modules`
--

CREATE TABLE `tr_modules` (
  `mod_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) NOT NULL,
  `rank` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_modules_configs`
--

CREATE TABLE `tr_modules_configs` (
  `modconf_id` int(10) UNSIGNED NOT NULL,
  `mod_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_project`
--

CREATE TABLE `tr_project` (
  `projectid` int(10) NOT NULL,
  `name` varchar(45) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `language` varchar(5) NOT NULL,
  `sourcelink` text NOT NULL,
  `binairelink` text NOT NULL,
  `description` text NOT NULL,
  `img` text NOT NULL,
  `progress` tinyint(1) NOT NULL,
  `website` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_users`
--

CREATE TABLE `tr_users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) NOT NULL,
  `email` varchar(80) NOT NULL,
  `pass` varchar(80) NOT NULL,
  `rank` tinyint(1) UNSIGNED DEFAULT NULL,
  `registration_date` datetime NOT NULL,
  `last_connect` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `signature` varchar(100) DEFAULT NULL,
  `template` varchar(30) DEFAULT NULL,
  `langue` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_users_rights`
--

CREATE TABLE `tr_users_rights` (
  `right_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `zone` varchar(10) DEFAULT NULL,
  `page` varchar(10) DEFAULT NULL,
  `identifier` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tr_banned`
--
ALTER TABLE `tr_banned`
  ADD PRIMARY KEY (`ban_id`);

--
-- Indexes for table `tr_blocks`
--
ALTER TABLE `tr_blocks`
  ADD PRIMARY KEY (`block_id`);

--
-- Indexes for table `tr_blocks_configs`
--
ALTER TABLE `tr_blocks_configs`
  ADD PRIMARY KEY (`bconfig_id`),
  ADD KEY `block_id` (`block_id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tr_blocks_visibility`
--
ALTER TABLE `tr_blocks_visibility`
  ADD PRIMARY KEY (`bvisibility_id`),
  ADD KEY `block_id` (`block_id`),
  ADD KEY `mod_id` (`mod_id`);

--
-- Indexes for table `tr_configs`
--
ALTER TABLE `tr_configs`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tr_menus`
--
ALTER TABLE `tr_menus`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `tr_modules`
--
ALTER TABLE `tr_modules`
  ADD PRIMARY KEY (`mod_id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tr_modules_configs`
--
ALTER TABLE `tr_modules_configs`
  ADD PRIMARY KEY (`modconf_id`),
  ADD KEY `name` (`name`),
  ADD KEY `mod_id` (`mod_id`);

--
-- Indexes for table `tr_project`
--
ALTER TABLE `tr_project`
  ADD PRIMARY KEY (`projectid`);

--
-- Indexes for table `tr_users`
--
ALTER TABLE `tr_users`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `name` (`name`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `tr_users_rights`
--
ALTER TABLE `tr_users_rights`
  ADD PRIMARY KEY (`right_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tr_banned`
--
ALTER TABLE `tr_banned`
  MODIFY `ban_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_blocks`
--
ALTER TABLE `tr_blocks`
  MODIFY `block_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_blocks_configs`
--
ALTER TABLE `tr_blocks_configs`
  MODIFY `bconfig_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_blocks_visibility`
--
ALTER TABLE `tr_blocks_visibility`
  MODIFY `bvisibility_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_configs`
--
ALTER TABLE `tr_configs`
  MODIFY `config_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_menus`
--
ALTER TABLE `tr_menus`
  MODIFY `menu_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_modules`
--
ALTER TABLE `tr_modules`
  MODIFY `mod_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_modules_configs`
--
ALTER TABLE `tr_modules_configs`
  MODIFY `modconf_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_project`
--
ALTER TABLE `tr_project`
  MODIFY `projectid` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_users`
--
ALTER TABLE `tr_users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_users_rights`
--
ALTER TABLE `tr_users_rights`
  MODIFY `right_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `tr_blocks_configs`
--
ALTER TABLE `tr_blocks_configs`
  ADD CONSTRAINT `tr_blocks_configs_block_id` FOREIGN KEY (`block_id`) REFERENCES `tr_blocks` (`block_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_blocks_visibility`
--
ALTER TABLE `tr_blocks_visibility`
  ADD CONSTRAINT `tr_blocks_visibility_block_id` FOREIGN KEY (`block_id`) REFERENCES `tr_blocks` (`block_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tr_blocks_visibility_mod_id` FOREIGN KEY (`mod_id`) REFERENCES `tr_modules` (`mod_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_modules_configs`
--
ALTER TABLE `tr_modules_configs`
  ADD CONSTRAINT `tr_modules_configs_mod_id` FOREIGN KEY (`mod_id`) REFERENCES `tr_modules` (`mod_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_users_rights`
--
ALTER TABLE `tr_users_rights`
  ADD CONSTRAINT `tr_users_rights_user_id` FOREIGN KEY (`user_id`) REFERENCES `tr_users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
