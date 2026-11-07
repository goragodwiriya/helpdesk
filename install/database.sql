-- -------------------------------------------------------------------------
-- install/database.sql — ข้อมูลตั้งต้นของ helpdesk
--
-- ตารางแกนของ Gcms (user, category, logs, login_attempt, number, migration,
-- user_meta, user_session, language) อยู่ใน install/core.sql
-- ตารางของโมดูล helpdesk อยู่ใน modules/helpdesk/install/database.sql
-- ไฟล์นี้จึงเหลือเฉพาะข้อมูลตั้งต้นที่ลงในตารางแกน ซึ่งไม่มีโมดูลไหนเป็นเจ้าของ
-- -------------------------------------------------------------------------

-- -------------------------------------------------------------------------
-- install/database.sql — ตารางและข้อมูลตัวอย่างของ helpdesk
--
-- ตารางแกนของ Gcms (user, category, logs, login_attempt, number, user_meta,
-- user_session, language) อยู่ใน install/core.sql ซึ่งเหมือนกันทุกโปรเจ็ค
-- ไฟล์นี้เก็บเฉพาะสิ่งที่เป็นของโปรเจ็คนี้เท่านั้น
--
-- ข้อกำหนด: InnoDB + utf8mb4 และเขียน PRIMARY KEY/KEY ไว้ในคำสั่ง CREATE TABLE
-- เลย เพื่อให้ตารางที่ตัวปรับรุ่นสร้างจากไฟล์นี้ได้ index ครบตั้งแต่แรก
-- -------------------------------------------------------------------------

INSERT INTO `{prefix}_category` (`type`, `category_id`, `topic`, `color`, `is_active`) VALUES
('department', '1', 'บริหาร', NULL, 1),
('department', '2', 'จัดซื้อจัดจ้าง', NULL, 1),
('department', '3', 'บุคคล', NULL, 1);
INSERT INTO `{prefix}_category` (`type`, `category_id`, `language`, `topic`, `color`, `is_active`) VALUES
('ticketstatus', '1', '', 'Open', '#660000', 1),
('ticketstatus', '2', '', 'In Progress', '#4A148C', 1),
('ticketstatus', '3', '', 'Pending', '#994400', 1),
('ticketstatus', '4', '', 'Resolved', '#006628', 1),
('ticketstatus', '5', '', 'Closed', '#FF0000', 1),
('ticketstatus', '6', '', 'Reopened', '#827717', 1),
('ticketstatus', '7', '', 'On Hold', '#000000', 1),
('ticketpriority', '1', '', 'Low', '#33691E', 1),
('ticketpriority', '2', '', 'Medium', '#4A148C', 1),
('ticketpriority', '3', '', 'Urgent', '#FF6F00', 1),
('ticketpriority', '4', '', 'Highly', '#B71C1C', 1),
('category', '1', '', 'ทั่วไป', NULL, 1),
('category', '2', '', 'การติดตั้ง', NULL, 1),
('category', '3', '', 'เกี่ยวกับ คชสาร', NULL, 1);
