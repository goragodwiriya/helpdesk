<?php
/**
 * รัน install/upgrade2.php ของจริงกับฐานข้อมูลทดสอบ
 * ใช้สำเนาโปรเจกต์ใน $sandbox เพื่อไม่แตะไฟล์ settings ของโปรเจกต์จริง
 */
$sandbox = $argv[1];
$_POST['username'] = 'admin';
$_POST['password'] = 'secret123';
$_REQUEST['step'] = 2;

define('ROOT_PATH', rtrim($sandbox, '/').'/');
$new_config = include ROOT_PATH.'install/settings/config.php';
$config = include ROOT_PATH.'settings/config.php';
$new_config['version'] = '7.0.3';

chdir(ROOT_PATH.'install');
ob_start();
include ROOT_PATH.'install/upgrade2.php';
$html = ob_get_clean();

echo strip_tags(str_replace(['</li>', '</p>', '</h2>'], ["\n", "\n", "\n"], $html)), "\n";
