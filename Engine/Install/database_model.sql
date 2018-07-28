-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2018 at 10:28 AM
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
  `banned_id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(50) NOT NULL,
  `name` varchar(45) NOT NULL,
  `email` varchar(80) DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `reason` text NOT NULL,
  `banishment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` tinyint(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `all_modules` tinyint(1) NOT NULL DEFAULT '0',
  `called_by_type` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_blocks_configs`
--

CREATE TABLE `tr_blocks_configs` (
  `block_config_id` int(10) UNSIGNED NOT NULL,
  `block_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_blocks_visibility`
--

CREATE TABLE `tr_blocks_visibility` (
  `block_visibility_id` int(10) UNSIGNED NOT NULL,
  `block_id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL
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
  `sublevel` smallint(10) UNSIGNED NOT NULL DEFAULT '0',
  `position` smallint(10) UNSIGNED NOT NULL DEFAULT '0',
  `rank` tinyint(1) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_menus_configs`
--

CREATE TABLE `tr_menus_configs` (
  `menu_config_id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_modules`
--

CREATE TABLE `tr_modules` (
  `module_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) NOT NULL,
  `rank` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tr_modules_configs`
--

CREATE TABLE `tr_modules_configs` (
  `module_config_id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
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
  `user_right_id` int(10) UNSIGNED NOT NULL,
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
  ADD PRIMARY KEY (`banned_id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `name` (`name`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`) USING BTREE;

--
-- Indexes for table `tr_blocks`
--
ALTER TABLE `tr_blocks`
  ADD PRIMARY KEY (`block_id`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `tr_blocks_configs`
--
ALTER TABLE `tr_blocks_configs`
  ADD PRIMARY KEY (`block_config_id`),
  ADD KEY `block_id` (`block_id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tr_blocks_visibility`
--
ALTER TABLE `tr_blocks_visibility`
  ADD PRIMARY KEY (`block_visibility_id`),
  ADD KEY `block_id` (`block_id`),
  ADD KEY `module_id` (`module_id`) USING BTREE;

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
  ADD PRIMARY KEY (`menu_id`),
  ADD KEY `block_id` (`block_id`),
  ADD KEY `menus_parent_id` (`parent_id`);

--
-- Indexes for table `tr_menus_configs`
--
ALTER TABLE `tr_menus_configs`
  ADD PRIMARY KEY (`menu_config_id`),
  ADD KEY `menu_id` (`menu_id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tr_modules`
--
ALTER TABLE `tr_modules`
  ADD PRIMARY KEY (`module_id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tr_modules_configs`
--
ALTER TABLE `tr_modules_configs`
  ADD PRIMARY KEY (`module_config_id`),
  ADD KEY `name` (`name`),
  ADD KEY `module_id` (`module_id`) USING BTREE;

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
  ADD PRIMARY KEY (`user_right_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tr_banned`
--
ALTER TABLE `tr_banned`
  MODIFY `banned_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_blocks`
--
ALTER TABLE `tr_blocks`
  MODIFY `block_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_blocks_configs`
--
ALTER TABLE `tr_blocks_configs`
  MODIFY `block_config_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_blocks_visibility`
--
ALTER TABLE `tr_blocks_visibility`
  MODIFY `block_visibility_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
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
-- AUTO_INCREMENT for table `tr_menus_configs`
--
ALTER TABLE `tr_menus_configs`
  MODIFY `menu_config_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_modules`
--
ALTER TABLE `tr_modules`
  MODIFY `module_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tr_modules_configs`
--
ALTER TABLE `tr_modules_configs`
  MODIFY `module_config_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
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
  MODIFY `user_right_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `tr_banned`
--
ALTER TABLE `tr_banned`
  ADD CONSTRAINT `banned_user_id` FOREIGN KEY (`user_id`) REFERENCES `tr_users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `tr_blocks_configs`
--
ALTER TABLE `tr_blocks_configs`
  ADD CONSTRAINT `blocks_configs_block_id` FOREIGN KEY (`block_id`) REFERENCES `tr_blocks` (`block_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_blocks_visibility`
--
ALTER TABLE `tr_blocks_visibility`
  ADD CONSTRAINT `blocks_visibility_block_id` FOREIGN KEY (`block_id`) REFERENCES `tr_blocks` (`block_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocks_visibility_module_id` FOREIGN KEY (`module_id`) REFERENCES `tr_modules` (`module_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_menus`
--
ALTER TABLE `tr_menus`
  ADD CONSTRAINT `menus_block_id` FOREIGN KEY (`block_id`) REFERENCES `tr_blocks` (`block_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menus_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `tr_menus` (`menu_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_menus_configs`
--
ALTER TABLE `tr_menus_configs`
  ADD CONSTRAINT `menus_configs_menu_id` FOREIGN KEY (`menu_id`) REFERENCES `tr_menus` (`menu_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_modules_configs`
--
ALTER TABLE `tr_modules_configs`
  ADD CONSTRAINT `modules_configs_module_id` FOREIGN KEY (`module_id`) REFERENCES `tr_modules` (`module_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_users_rights`
--
ALTER TABLE `tr_users_rights`
  ADD CONSTRAINT `users_rights_user_id` FOREIGN KEY (`user_id`) REFERENCES `tr_users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
