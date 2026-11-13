-- ---------------------------------------------------------------------------
-- modules/helpdesk/install/database.sql — ตารางที่โมดูล helpdesk เป็นเจ้าของ
--
-- **ประกาศที่นี่ที่เดียว** ห้ามประกาศซ้ำใน install/database.sql ของโปรเจ็ค
-- และห้ามเขียน CREATE TABLE ซ้ำไว้ในตัวปรับรุ่นอีกชุด
-- ประกาศสองที่ = ติดตั้งใหม่ล้มด้วย "Table already exists" และนิยามสองชุด
-- จะค่อย ๆ ต่างกันจนไซต์ที่อัปเกรดคนละเส้นทางได้สคีมาไม่เหมือนกัน
--
-- ทั้งการติดตั้งใหม่ (common.php::schemaFiles) และการปรับรุ่น (ensureTable)
-- อ่านนิยามจากไฟล์นี้ไฟล์เดียว
-- ---------------------------------------------------------------------------

CREATE TABLE `{prefix}_helpdesk` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`customer_id` int(11) NOT NULL,
`agent_id` int(11) NOT NULL DEFAULT 0,
`ticket_no` varchar(20) DEFAULT NULL,
`category` varchar(10) NOT NULL,
`priority` varchar(10) NOT NULL,
`subject` varchar(150) NOT NULL,
`detail` text NOT NULL,
`status` tinyint(2) NOT NULL,
`due_date` date DEFAULT NULL,
`created_at` datetime NOT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ticket_no` (`ticket_no`),
KEY `customer_id` (`customer_id`),
KEY `agent_id` (`agent_id`),
KEY `idx_status_created` (`status`,`created_at`),
KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `{prefix}_helpdesk_status` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`helpdesk_id` int(11) NOT NULL,
`comment` text NOT NULL,
`agent_id` int(11) NOT NULL DEFAULT 0,
`member_id` int(11) NOT NULL DEFAULT 0,
`created_at` datetime NOT NULL,
`private` tinyint(1) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `helpdesk_id` (`helpdesk_id`),
KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
