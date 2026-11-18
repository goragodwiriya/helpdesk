-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 24, 2024 at 08:57 AM
-- Server version: 10.6.18-MariaDB-0ubuntu0.22.04.1
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Table structure for table `app_category`
--

CREATE TABLE `app_category` (
  `type` varchar(20) NOT NULL,
  `category_id` varchar(10) DEFAULT '0',
  `language` varchar(2) DEFAULT '',
  `topic` varchar(150) NOT NULL,
  `color` varchar(16) DEFAULT NULL,
  `published` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `app_category`
--

INSERT INTO `app_category` (`type`, `category_id`, `topic`, `color`, `published`) VALUES
('ticketstatus', '1', 'Open', '#660000', 1),
('ticketstatus', '2', 'In Progress', '#4A148C', 1),
('ticketstatus', '3', 'Pending', '#994400', 1),
('ticketstatus', '4', 'Resolved', '#006628', 1),
('ticketstatus', '5', 'Closed', '#FF0000', 1),
('ticketstatus', '6', 'Reopened', '#827717', 1),
('ticketstatus', '7', 'On Hold', '#000000', 1),
('category', '1', 'ทั่วไป', NULL, 1),
('category', '2', 'การติดตั้ง', NULL, 1),
('category', '3', 'เกี่ยวกับ คชสาร', NULL, 1),
('ticketpriority', '1', 'Low', '#33691E', 1),
('ticketpriority', '2', 'Medium', '#4A148C', 1),
('ticketpriority', '3', 'Urgent', '#FF6F00', 1),
('ticketpriority', '4', 'Highly', '#B71C1C', 1);

-- --------------------------------------------------------

--
-- Table structure for table `app_helpdesk`
--

CREATE TABLE `app_helpdesk` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `ticket_no` varchar(20) DEFAULT NULL,
  `category` varchar(10) NOT NULL,
  `priority` varchar(10) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `detail` varchar(1000) NOT NULL,
  `status` tinyint(2) NOT NULL,
  `create_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_helpdesk_status`
--

CREATE TABLE `app_helpdesk_status` (
  `id` int(11) NOT NULL,
  `helpdesk_id` int(11) NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `create_date` datetime NOT NULL,
  `private` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_language`
--

CREATE TABLE `app_language` (
  `id` int(11) NOT NULL,
  `key` text NOT NULL,
  `type` varchar(5) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `js` tinyint(1) NOT NULL,
  `th` text DEFAULT NULL,
  `en` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_logs`
--

CREATE TABLE `app_logs` (
  `id` int(11) NOT NULL,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `create_date` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `topic` text NOT NULL,
  `datas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_number`
--

CREATE TABLE `app_number` (
  `type` varchar(20) NOT NULL,
  `key` varchar(10) DEFAULT '',
  `prefix` varchar(20) DEFAULT '',
  `auto_increment` int(11) NOT NULL,
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_user`
--

CREATE TABLE `app_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `salt` varchar(32) DEFAULT '',
  `password` varchar(50) NOT NULL,
  `token` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `permission` text DEFAULT '',
  `name` varchar(150) NOT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `provinceID` varchar(3) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'TH',
  `create_date` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `social` tinyint(1) DEFAULT 0,
  `line_uid` varchar(33) DEFAULT NULL,
  `telegram_id` varchar(13) DEFAULT NULL,
  `activatecode` varchar(32) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_user_meta`
--

CREATE TABLE `app_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Indexes for table `app_category`
--
ALTER TABLE `app_category`
  ADD KEY `type` (`type`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `language` (`language`);

--
-- Indexes for table `app_helpdesk`
--
ALTER TABLE `app_helpdesk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_no` (`ticket_no`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `app_helpdesk_status`
--
ALTER TABLE `app_helpdesk_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `helpdesk_id` (`helpdesk_id`) USING BTREE,
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `app_language`
--
ALTER TABLE `app_language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `app_logs`
--
ALTER TABLE `app_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `src_id` (`src_id`),
  ADD KEY `module` (`module`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `app_number`
--
ALTER TABLE `app_number`
  ADD PRIMARY KEY (`type`,`prefix`);

--
-- Indexes for table `app_user`
--
ALTER TABLE `app_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `line_uid` (`line_uid`),
  ADD KEY `username` (`username`),
  ADD KEY `token` (`token`),
  ADD KEY `phone` (`phone`),
  ADD KEY `id_card` (`id_card`),
  ADD KEY `activatecode` (`activatecode`);

--
-- Indexes for table `app_user_meta`
--
ALTER TABLE `app_user_meta`
  ADD KEY `member_id` (`member_id`,`name`);

--
-- AUTO_INCREMENT for table `app_helpdesk`
--
ALTER TABLE `app_helpdesk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_helpdesk_status`
--
ALTER TABLE `app_helpdesk_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_language`
--
ALTER TABLE `app_language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_logs`
--
ALTER TABLE `app_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_user`
--
ALTER TABLE `app_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
