<?php
/**
 * modules/helpdesk/install/upgrade.php — พาฐานเดิมมาถึงสคีมาของโมดูล helpdesk
 *
 * install/upgrade_core.php เรียกไฟล์นี้ให้เอง ตัวแปรที่ใช้ได้คือชุดเดียวกับที่
 * upgrade_core ใช้ : $db, $db_config, $prefix, $content, $config
 *
 * ⚠️ ก่อนมีไฟล์นี้ งานทั้งหมดนี้ฝังอยู่ใน install/upgrade2.php ของโปรเจ็ค โดยมี
 * CREATE TABLE เขียนซ้ำไว้อีกชุดหนึ่ง นิยามสองชุดคือสิ่งที่วันหนึ่งจะต่างกันเงียบ ๆ
 * แล้วไซต์ที่ติดตั้งใหม่กับไซต์ที่ปรับรุ่นจะได้ตารางคนละหน้าตาโดยไม่มีอะไรฟ้อง
 * ตอนนี้นิยามอยู่ที่ modules/helpdesk/install/database.sql ที่เดียว
 *
 * กฎเดียวกับ upgrade_core : ทุกเงื่อนไขถามว่า "ต้องแก้ไหม" ไม่ใช่ "ตอนนี้เป็นอะไร"
 */
if (!defined('ROOT_PATH')) {
    exit;
}

// =========================================================
// helpdesk
// =========================================================
$table_helpdesk = $prefix.'_helpdesk';
// นิยามตารางอยู่ที่ modules/helpdesk/install/database.sql ที่เดียว
if (ensureTable($db, $prefix, $table_helpdesk)) {
    $content[] = '<li class="correct">helpdesk: สร้างตารางใหม่</li>';
} else {
    // create_date -> created_at (ย้ายข้อมูลก่อนลบคอลัมน์เดิม)
    if (!$db->fieldExists($table_helpdesk, 'created_at')) {
        $db->query("ALTER TABLE `$table_helpdesk` ADD `created_at` DATETIME NULL");
        if ($db->fieldExists($table_helpdesk, 'create_date')) {
            $db->query("UPDATE `$table_helpdesk` SET `created_at` = `create_date`");
        }
        $db->query("UPDATE `$table_helpdesk` SET `created_at` = NOW() WHERE `created_at` IS NULL");
        $db->query("ALTER TABLE `$table_helpdesk` MODIFY `created_at` DATETIME NOT NULL");
        $content[] = '<li class="correct">helpdesk: เพิ่ม created_at</li>';
    }
    if ($db->fieldExists($table_helpdesk, 'create_date')) {
        $db->query("ALTER TABLE `$table_helpdesk` DROP COLUMN `create_date`");
        $content[] = '<li class="correct">helpdesk: ลบ create_date</li>';
    }
    // agent_id เจ้าของงาน backfill จาก agent คนแรกที่ตอบ
    if (!$db->fieldExists($table_helpdesk, 'agent_id')) {
        $db->query("ALTER TABLE `$table_helpdesk` ADD `agent_id` INT(11) NOT NULL DEFAULT 0 AFTER `customer_id`");
        // ⚠️ ต้องเช็คว่า helpdesk_status มี agent_id ก่อนอ่านจากมัน
        // ไซต์รุ่นเก่าที่สุดยังไม่มีคอลัมน์นั้นทั้งสองตาราง การ backfill จึงล้ม
        // ด้วย "Unknown column 'S.agent_id'" แล้วปรับรุ่นไม่ผ่านทั้งชุด
        // ถ้าไม่มีให้ปล่อยเป็น 0 (ค่าปริยาย) = ยังไม่มีเจ้าของงาน ซึ่งถูกต้อง
        $_hs = $prefix.'_helpdesk_status';
        if ($db->tableExists($_hs) && $db->fieldExists($_hs, 'agent_id')) {
            $_col = $db->fieldExists($_hs, 'created_at') ? 'created_at' : 'create_date';
            $db->query("UPDATE `$table_helpdesk` R SET R.`agent_id` = COALESCE((
                SELECT S.`agent_id` FROM `$_hs` S
                WHERE S.`helpdesk_id` = R.`id` AND S.`agent_id` > 0
                ORDER BY S.`$_col`, S.`id` LIMIT 1
            ), 0)");
        }
        $content[] = '<li class="correct">helpdesk: เพิ่ม agent_id</li>';
    }
    // updated_at เวลาที่มีความเคลื่อนไหวล่าสุด
    if (!$db->fieldExists($table_helpdesk, 'updated_at')) {
        $db->query("ALTER TABLE `$table_helpdesk` ADD `updated_at` DATETIME NULL");
        if ($db->tableExists($prefix.'_helpdesk_status')) {
            $_hs = $prefix.'_helpdesk_status';
            $_col = $db->fieldExists($_hs, 'created_at') ? 'created_at' : 'create_date';
            $db->query("UPDATE `$table_helpdesk` R SET R.`updated_at` = (
                SELECT MAX(S.`$_col`) FROM `$_hs` S WHERE S.`helpdesk_id` = R.`id`
            )");
        }
        $db->query("UPDATE `$table_helpdesk` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL");
        $content[] = '<li class="correct">helpdesk: เพิ่ม updated_at</li>';
    }
    // due_date (SLA)
    if (!$db->fieldExists($table_helpdesk, 'due_date')) {
        $db->query("ALTER TABLE `$table_helpdesk` ADD `due_date` DATE NULL AFTER `status`");
        $content[] = '<li class="correct">helpdesk: เพิ่ม due_date</li>';
    }
    // แปลง charset ก่อนปรับชนิดคอลัมน์ เพราะ CONVERT TO utf8mb4
    // จะดัน TEXT ขึ้นเป็น MEDIUMTEXT ให้เอง
    // ⚠️ ต้องแปลง engine ด้วย ไม่ใช่แค่ charset — ของเดิมแปลงแต่ charset
    // ไซต์รุ่นเก่าจึงยังเป็น MyISAM ตลอดไป (ไม่มี transaction ไม่มี foreign key)
    if (convertToInnoDB($db, $table_helpdesk)) {
        $content[] = '<li class="correct">helpdesk: แปลงเป็น InnoDB</li>';
    }
    if (convertToUtf8mb4($db, $table_helpdesk)) {
        $content[] = '<li class="correct">helpdesk: แปลงเป็น utf8mb4</li>';
    }
    // ปรับทุกคอลัมน์ให้ตรงกับ database.sql (ชนิด NULL DEFAULT และลำดับ)
    $helpdesk_columns = [
        ['customer_id', 'int(11)', false, null, 'id'],
        ['agent_id', 'int(11)', false, '0', 'customer_id'],
        ['ticket_no', 'varchar(20)', true, null, 'agent_id'],
        ['category', 'varchar(10)', false, null, 'ticket_no'],
        ['priority', 'varchar(10)', false, null, 'category'],
        ['subject', 'varchar(150)', false, null, 'priority'],
        ['detail', 'text', false, null, 'subject'],
        ['status', 'tinyint(2)', false, null, 'detail'],
        ['due_date', 'date', true, null, 'status'],
        ['created_at', 'datetime', false, null, 'due_date'],
        ['updated_at', 'datetime', true, null, 'created_at']
    ];
    foreach ($helpdesk_columns as $_col) {
        if (ensureColumn($db, $table_helpdesk, $_col[0], $_col[1], $_col[2], $_col[3], $_col[4])) {
            $content[] = '<li class="correct">helpdesk: ปรับคอลัมน์ '.$_col[0].'</li>';
        }
    }
    if (!$db->indexExists($table_helpdesk, 'PRIMARY')) {
        $db->query("ALTER TABLE `$table_helpdesk` ADD PRIMARY KEY (`id`)");
    }
    if (!isAutoIncrement($db, $table_helpdesk, 'id')) {
        $db->query("ALTER TABLE `$table_helpdesk` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
        $content[] = '<li class="correct">helpdesk: กำหนด AUTO_INCREMENT</li>';
    }
    if (!$db->indexExists($table_helpdesk, 'ticket_no')) {
        $db->query("ALTER TABLE `$table_helpdesk` ADD UNIQUE KEY `ticket_no` (`ticket_no`)");
    }
    foreach ([
        'customer_id' => '(`customer_id`)',
        'agent_id' => '(`agent_id`)',
        'idx_status_created' => '(`status`,`created_at`)',
        'idx_due_date' => '(`due_date`)'
    ] as $_idx => $_cols) {
        if (!$db->indexExists($table_helpdesk, $_idx)) {
            $db->query("ALTER TABLE `$table_helpdesk` ADD INDEX `$_idx` $_cols");
        }
    }
    $content[] = '<li class="correct">helpdesk อัปเกรดสำเร็จ</li>';
}

// =========================================================
// helpdesk_status
// =========================================================
$table_helpdesk_status = $prefix.'_helpdesk_status';
// นิยามตารางอยู่ที่ modules/helpdesk/install/database.sql ที่เดียว
if (ensureTable($db, $prefix, $table_helpdesk_status)) {
    $content[] = '<li class="correct">helpdesk_status: สร้างตารางใหม่</li>';
} else {
    if (!$db->fieldExists($table_helpdesk_status, 'created_at')) {
        $db->query("ALTER TABLE `$table_helpdesk_status` ADD `created_at` DATETIME NULL");
        if ($db->fieldExists($table_helpdesk_status, 'create_date')) {
            $db->query("UPDATE `$table_helpdesk_status` SET `created_at` = `create_date`");
        }
        $db->query("UPDATE `$table_helpdesk_status` SET `created_at` = NOW() WHERE `created_at` IS NULL");
        $db->query("ALTER TABLE `$table_helpdesk_status` MODIFY `created_at` DATETIME NOT NULL");
        $content[] = '<li class="correct">helpdesk_status: เพิ่ม created_at</li>';
    }
    if ($db->fieldExists($table_helpdesk_status, 'create_date')) {
        $db->query("ALTER TABLE `$table_helpdesk_status` DROP COLUMN `create_date`");
        $content[] = '<li class="correct">helpdesk_status: ลบ create_date</li>';
    }
    // ⚠️ ต้องแปลง engine ด้วย ไม่ใช่แค่ charset — ของเดิมแปลงแต่ charset
    // ไซต์รุ่นเก่าจึงยังเป็น MyISAM ตลอดไป (ไม่มี transaction ไม่มี foreign key)
    if (convertToInnoDB($db, $table_helpdesk_status)) {
        $content[] = '<li class="correct">helpdesk_status: แปลงเป็น InnoDB</li>';
    }
    if (convertToUtf8mb4($db, $table_helpdesk_status)) {
        $content[] = '<li class="correct">helpdesk_status: แปลงเป็น utf8mb4</li>';
    }
    $status_columns = [
        ['helpdesk_id', 'int(11)', false, null, 'id'],
        ['comment', 'text', false, null, 'helpdesk_id'],
        ['agent_id', 'int(11)', false, '0', 'comment'],
        ['member_id', 'int(11)', false, '0', 'agent_id'],
        ['created_at', 'datetime', false, null, 'member_id'],
        ['private', 'tinyint(1)', false, '0', 'created_at']
    ];
    foreach ($status_columns as $_col) {
        if (ensureColumn($db, $table_helpdesk_status, $_col[0], $_col[1], $_col[2], $_col[3], $_col[4])) {
            $content[] = '<li class="correct">helpdesk_status: ปรับคอลัมน์ '.$_col[0].'</li>';
        }
    }
    if (!$db->indexExists($table_helpdesk_status, 'PRIMARY')) {
        $db->query("ALTER TABLE `$table_helpdesk_status` ADD PRIMARY KEY (`id`)");
    }
    if (!isAutoIncrement($db, $table_helpdesk_status, 'id')) {
        $db->query("ALTER TABLE `$table_helpdesk_status` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
        $content[] = '<li class="correct">helpdesk_status: กำหนด AUTO_INCREMENT</li>';
    }
    // ดัชนีของระบบเดิมประกาศเป็น USING BTREE ทำให้สคีมาไม่ตรงกับการติดตั้งใหม่
    $_hs_indexes = $db->customQuery("SHOW INDEX FROM `$table_helpdesk_status` WHERE `Key_name` = 'helpdesk_id'");
    if (!empty($_hs_indexes) && strtoupper($_hs_indexes[0]->Index_type) === 'BTREE'
        && stripos($db->customQuery("SHOW CREATE TABLE `$table_helpdesk_status`")[0]->{'Create Table'}, '`helpdesk_id`) USING BTREE') !== false) {
        $db->query("ALTER TABLE `$table_helpdesk_status` DROP INDEX `helpdesk_id`");
        $content[] = '<li class="correct">helpdesk_status: สร้างดัชนี helpdesk_id ใหม่</li>';
    }
    foreach (['helpdesk_id' => '(`helpdesk_id`)', 'agent_id' => '(`agent_id`)'] as $_idx => $_cols) {
        if (!$db->indexExists($table_helpdesk_status, $_idx)) {
            $db->query("ALTER TABLE `$table_helpdesk_status` ADD INDEX `$_idx` $_cols");
        }
    }
    $content[] = '<li class="correct">helpdesk_status อัปเกรดสำเร็จ</li>';
}

// =========================================================
// category: หมวดหมู่ตั้งต้นของ helpdesk (เติมเฉพาะประเภทที่ยังไม่มี)
// =========================================================
$helpdesk_seed = [
    'ticketstatus' => [
        ['1', 'Open', '#660000'], ['2', 'In Progress', '#4A148C'],
        ['3', 'Pending', '#994400'], ['4', 'Resolved', '#006628'],
        ['5', 'Closed', '#FF0000'], ['6', 'Reopened', '#827717'],
        ['7', 'On Hold', '#000000']
    ],
    'ticketpriority' => [
        ['1', 'Low', '#33691E'], ['2', 'Medium', '#4A148C'],
        ['3', 'Urgent', '#FF6F00'], ['4', 'Highly', '#B71C1C']
    ],
    'category' => [
        ['1', 'ทั่วไป', null], ['2', 'การติดตั้ง', null], ['3', 'เกี่ยวกับ คชสาร', null]
    ]
];
foreach ($helpdesk_seed as $_type => $_rows) {
    $_found = $db->customQuery("SELECT COUNT(*) AS `count` FROM `$table_category` WHERE `type` = '$_type'");
    if (empty($_found[0]->count)) {
        foreach ($_rows as $_row) {
            $db->insert($table_category, [
                'type' => $_type,
                'category_id' => $_row[0],
                'language' => '',
                'topic' => $_row[1],
                'color' => $_row[2],
                'is_active' => 1
            ]);
        }
        $content[] = '<li class="correct">category: เพิ่มข้อมูลตั้งต้นของ '.$_type.'</li>';
    }
}

// =========================================================
// helpdesk: ค่ากำหนดของโมดูล
// =========================================================
$helpdesk_defaults = [
    'helpdesk_w' => 600,
    'helpdesk_img_typies' => ['jpg', 'jpeg', 'png', 'webp'],
    'helpdesk_no' => '%04d',
    'helpdesk_prefix' => 'TICKET%Y%M-',
    'helpdesk_first_status' => 1,
    'helpdesk_closed_status' => 5,
    'helpdesk_reopened_status' => 6,
    'helpdesk_sla_days' => 0,
    'helpdesk_mail_user' => '',
    'helpdesk_mail_agent' => ''
];
foreach ($helpdesk_defaults as $_key => $_value) {
    if (!isset($config[$_key])) {
        $config[$_key] = $_value;
    }
}
