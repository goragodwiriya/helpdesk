<?php
/**
 * bootstrap ของ harness: โหลดเฟรมเวิร์กและปลอมการล็อกอิน
 */
chdir(getenv('HD_ROOT'));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/api';
$_SERVER['SCRIPT_NAME'] = '/api.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include 'load.php';
Kotchasan::createWebApplication('Gcms\Config');
$cfg = \Kotchasan\Config::create();

/**
 * สร้าง Request พร้อม token ของผู้ใช้ที่ระบุ
 *
 * @param int $memberId
 * @param string $method
 * @param array $data
 *
 * @return \Kotchasan\Http\Request
 */
function hd_request($memberId, $method = 'GET', array $data = [])
{
    global $cfg;
    $jwt = \Kotchasan\Jwt::encode(['sub' => $memberId, 'exp' => time() + 3600], $cfg->jwt_secret);
    $csrf = bin2hex(random_bytes(32));
    $_SESSION[$csrf] = ['times' => 0, 'expired' => time() + 3600, 'created' => time()];
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$jwt;
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf;
    $_SERVER['REQUEST_METHOD'] = $method;
    $request = new \Kotchasan\Http\Request();
    if ($method === 'GET') {
        $_GET = $data;
        $_REQUEST = $data;
        return $request->withQueryParams($data);
    }
    $_POST = $data;
    $_REQUEST = $data;
    return $request->withParsedBody($data);
}

/**
 * เรียก controller แล้วคืนค่า response ที่ decode แล้ว
 *
 * @param object $controller
 * @param string $method
 * @param \Kotchasan\Http\Request $request
 *
 * @return array
 */
function hd_call($controller, $method, $request)
{
    $response = $controller->{$method}($request);
    return json_decode((string) $response->getBody(), true);
}

$GLOBALS['hd_pass'] = 0;
$GLOBALS['hd_fail'] = 0;

/**
 * ตรวจผลลัพธ์
 *
 * @param string $label
 * @param mixed $actual
 * @param mixed $expected
 */
function ok($label, $actual, $expected = true)
{
    if ($actual === $expected) {
        $GLOBALS['hd_pass']++;
    } else {
        $GLOBALS['hd_fail']++;
        echo "  FAIL  $label\n        expected: ".var_export($expected, true)."\n        actual  : ".var_export($actual, true)."\n";
    }
}

/**
 * สรุปผล
 */
function summary()
{
    echo "\n".str_repeat('=', 50)."\n";
    printf("ผ่าน %d / ล้มเหลว %d\n", $GLOBALS['hd_pass'], $GLOBALS['hd_fail']);
    exit($GLOBALS['hd_fail'] > 0 ? 1 : 0);
}
