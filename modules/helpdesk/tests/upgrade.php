<?php
/**
 * รัน install/upgrade2.php ของจริงกับฐานข้อมูลทดสอบ
 * ใช้สำเนาโปรเจกต์ใน $sandbox เพื่อไม่แตะไฟล์ settings ของโปรเจกต์จริง
 */
$sandbox = $argv[1];
$_POST['username'] = 'admin';
$_POST['password'] = 'secret123';
// upgrade2.php asks the person to confirm a backup first (install/cli-upgrade.php does the same)
$_POST['confirm_backup'] = 1;
$_REQUEST['step'] = 2;

define('ROOT_PATH', rtrim($sandbox, '/').'/');
// upgrade2.php uses the installer helpers (adminForm, updateAdmin, ...), as install/cli-upgrade.php loads them
include_once ROOT_PATH.'install/common.php';
$new_config = include ROOT_PATH.'install/settings/config.php';
$config = include ROOT_PATH.'settings/config.php';
$new_config['version'] = '7.0.3';

chdir(ROOT_PATH.'install');
ob_start();
include ROOT_PATH.'install/upgrade2.php';
$html = ob_get_clean();

echo strip_tags(str_replace(['</li>', '</p>', '</h2>'], ["\n", "\n", "\n"], $html)), "\n";
