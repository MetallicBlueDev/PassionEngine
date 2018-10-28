-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2018 at 10:32 AM
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

--
-- Dumping data for table `tr_blocks`
--

INSERT INTO `tr_blocks` (`block_id`, `side`, `position`, `title`, `type`, `rank`, `all_modules`, `called_by_type`) VALUES
(1, 1, 1, 'Login', 'Login', 0, 0, 1);

--
-- Dumping data for table `tr_blocks_visibility`
--

INSERT INTO `tr_blocks_visibility` (`block_visibility_id`, `block_id`, `module_id`) VALUES
(1, 1, 7);

--
-- Dumping data for table `tr_configs`
--

INSERT INTO `tr_configs` (`config_id`, `name`, `value`) VALUES
(12, 'registrationAllowed', '1'),
(13, 'cookiePrefix', 'tr'),
(14, 'sessionTimeLimit', '30'),
(15, 'cryptKey', '1234'),
(16, 'captchaMode', 'off'),
(17, 'defaultAdministratorEmail', 'test@test.net'),
(18, 'defaultSiteName', 'Trancer-Studio'),
(19, 'defaultSiteSlogan', 'C\'est trop de la balle'),
(20, 'defaultSiteStatut', 'open'),
(21, 'defaultDescription', 'site de test'),
(22, 'defaultKeywords', 'PassionEngine test'),
(23, 'defaultLanguage', 'french'),
(24, 'defaultTemplate', 'Engine/Template/MetallicBlueSky'),
(25, 'urlRewriting', '0'),
(26, 'defaultSiteCloseReason', 'Site is closed'),
(27, 'defaultModule', 'home');

--
-- Dumping data for table `tr_modules`
--

INSERT INTO `tr_modules` (`module_id`, `name`, `rank`, `count`) VALUES
(7, 'Home', 0, NULL),
(8, 'testx', 0, NULL);

--
-- Dumping data for table `tr_users`
--

INSERT INTO `tr_users` (`user_id`, `name`, `email`, `pass`, `rank`, `registration_date`, `last_connect`, `avatar`, `website`, `signature`, `template`, `langue`) VALUES
(1, 'trancer', 'test@test.com', '1234', 0, '2018-07-06 00:00:00', '2018-07-06 18:20:16', NULL, NULL, NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
