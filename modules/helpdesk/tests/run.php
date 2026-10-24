<?php
/**
 * modules/helpdesk/tests/run.php
 *
 * ชุดทดสอบ end-to-end ของโมดูล helpdesk
 *   1. สร้างฐานข้อมูลทดสอบด้วยสคีมาและข้อมูลของ "ระบบเดิม" (fixtures/legacy_*.sql)
 *   2. รัน install/upgrade2.php ของจริง เพื่อพิสูจน์ว่าข้อมูลเดิมอัปเกรดได้
 *   3. เรียก Controller ของโมดูลตรง ๆ ตรวจพฤติกรรมทั้งหมด
 *
 * วิธีใช้ (รันจากรากโปรเจกต์)
 *   php modules/helpdesk/tests/run.php
 *
 * ตัวแปรแวดล้อมที่ปรับได้
 *   HD_TEST_DB   ชื่อฐานข้อมูลทดสอบ (ค่าปริยาย helpdesk_module_test)
 *
 * ชุดทดสอบสร้างและลบฐานข้อมูลของตัวเอง ไม่แตะฐานข้อมูลจริง
 */

$projectRoot = realpath(__DIR__.'/../../..');
$testsDir = __DIR__;
$testDb = getenv('HD_TEST_DB') ?: 'helpdesk_module_test';

if (!is_file($projectRoot.'/settings/database.php')) {
    fwrite(STDERR, "ไม่พบ settings/database.php กรุณารันจากรากโปรเจกต์\n");
    exit(2);
}

$dbConfig = include $projectRoot.'/settings/database.php';
$mysql = $dbConfig['mysql'];
if ($testDb === $mysql['dbname']) {
    fwrite(STDERR, "ชื่อฐานทดสอบซ้ำกับฐานข้อมูลจริง กรุณากำหนด HD_TEST_DB ให้ต่างออกไป\n");
    exit(2);
}

$dsn = 'mysql:host='.$mysql['hostname'].';port='.$mysql['port'].';charset=utf8mb4';
$pdo = new PDO($dsn, $mysql['username'], $mysql['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "1) สร้างฐานข้อมูลทดสอบ $testDb จากสคีมาของระบบเดิม\n";
$pdo->exec("DROP DATABASE IF EXISTS `$testDb`");
// ระบบเดิมเป็น utf8 (utf8mb3) ตั้งใจให้ตรงของจริง เพื่อทดสอบการแปลง charset ด้วย
$pdo->exec("CREATE DATABASE `$testDb` DEFAULT CHARACTER SET utf8");
$pdo->exec("USE `$testDb`");

/**
 * รันไฟล์ SQL ทีละคำสั่ง
 *
 * @param PDO $pdo
 * @param string $file
 * @param string $prefix
 *
 * @return void
 */
function runSqlFile(PDO $pdo, $file, $prefix)
{
    $sql = str_replace('{prefix}', $prefix, file_get_contents($file));
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

runSqlFile($pdo, $testsDir.'/fixtures/legacy_schema.sql', $mysql['prefix']);
runSqlFile($pdo, $testsDir.'/fixtures/legacy_data.sql', $mysql['prefix']);
// ผู้ดูแลสำหรับให้ตัวติดตั้งตรวจสิทธิ์ (รหัสผ่านรูปแบบเก่า sha1(password + salt))
$pdo->exec("UPDATE `".$mysql['prefix']."_user` SET `username` = 'admin', `password` = SHA1(CONCAT('secret123','abc')) WHERE `id` = 1");

echo "2) เตรียมสำเนาโปรเจกต์และรัน install/upgrade2.php ของจริง\n";
$sandbox = sys_get_temp_dir().'/helpdesk-tests-'.getmypid();
exec('rm -rf '.escapeshellarg($sandbox));
mkdir($sandbox, 0777, true);
exec('tar -cf - --exclude=node_modules --exclude=datas/cache -C '.escapeshellarg($projectRoot).' . | tar -xf - -C '.escapeshellarg($sandbox));

// สำเนาชี้ไปฐานทดสอบ และยังไม่มีค่ากำหนดของโมดูล เพื่อทดสอบว่าตัวติดตั้งเติมให้ครบ
$sandboxDb = str_replace("'dbname' => '".$mysql['dbname']."'", "'dbname' => '".$testDb."'", file_get_contents($projectRoot.'/settings/database.php'));
file_put_contents($sandbox.'/settings/database.php', $sandboxDb);
$sandboxConfig = include $projectRoot.'/settings/config.php';
foreach (array_keys($sandboxConfig) as $key) {
    if (strpos($key, 'helpdesk_') === 0) {
        unset($sandboxConfig[$key]);
    }
}
file_put_contents($sandbox.'/settings/config.php', '<'."?php\n/* config.php */\nreturn ".var_export($sandboxConfig, true).';');

$upgradeOutput = [];
exec('php '.escapeshellarg($testsDir.'/upgrade.php').' '.escapeshellarg($sandbox).' 2>&1', $upgradeOutput);
$upgradeText = implode("\n", $upgradeOutput);
$upgradeOk = strpos($upgradeText, 'ปรับรุ่นเรียบร้อย') !== false;
echo $upgradeOk ? "   ตัวติดตั้งทำงานสำเร็จ\n" : "   ตัวติดตั้งล้มเหลว\n$upgradeText\n";

echo "3) ทดสอบโมดูล\n";
$suites = ['test1', 'test2', 'test3', 'test4', 'test5'];
$pass = $upgradeOk ? 1 : 0;
$fail = $upgradeOk ? 0 : 1;
foreach ($suites as $suite) {
    $output = [];
    exec('HD_ROOT='.escapeshellarg($sandbox).' HD_BOOT='.escapeshellarg($testsDir.'/bootstrap.php')
        .' php '.escapeshellarg($testsDir.'/'.$suite.'.php').' 2>&1', $output);
    $text = implode("\n", $output);
    if (preg_match('/ผ่าน (\d+) \/ ล้มเหลว (\d+)/u', $text, $m)) {
        $pass += (int) $m[1];
        $fail += (int) $m[2];
        printf("   %-7s ผ่าน %3d / ล้มเหลว %d\n", $suite, $m[1], $m[2]);
        if ((int) $m[2] > 0) {
            foreach (explode("\n", $text) as $line) {
                if (strpos($line, 'FAIL') !== false || strpos($line, 'expected') !== false || strpos($line, 'actual') !== false) {
                    echo '     ', trim($line), "\n";
                }
            }
        }
    } else {
        $fail++;
        printf("   %-7s ไม่สามารถรันได้\n%s\n", $suite, $text);
    }
}

exec('rm -rf '.escapeshellarg($sandbox));
if (!getenv('HD_KEEP_DB')) {
    $pdo->exec("DROP DATABASE IF EXISTS `$testDb`");
}

echo str_repeat('=', 44), "\n";
printf("รวม: ผ่าน %d / ล้มเหลว %d\n", $pass, $fail);
exit($fail > 0 ? 1 : 0);
