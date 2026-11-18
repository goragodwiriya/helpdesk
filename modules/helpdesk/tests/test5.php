<?php
include getenv('HD_BOOT');

echo "--- เมนูและสิทธิ์ (auto-discovery) ---\n";
$json = json_encode(\Gcms\Controller::getPermissionOptions(null), JSON_UNESCAPED_UNICODE);
ok('รายการสิทธิ์มี can_manage_helpdesk', strpos($json, 'can_manage_helpdesk') !== false);
ok('รายการสิทธิ์มี helpdesk_agent', strpos($json, 'helpdesk_agent') !== false);

foreach ([[1, 'แอดมิน'], [2, 'agent'], [5, 'ผู้ใช้ทั่วไป']] as [$uid, $label]) {
    $login = \Index\Auth\Model::getUserById($uid);
    $json = json_encode(\Index\Menus\Controller::getMenus($login), JSON_UNESCAPED_UNICODE);
    ok("เมนูของ $label มี /helpdesk", preg_match('#/helpdesk"#', $json) === 1);
    ok("เมนูของ $label มี /helpdesk-receive", strpos($json, '/helpdesk-receive') !== false);
    $hasJobs = strpos($json, '/helpdesk-jobs') !== false;
    ok("เมนู /helpdesk-jobs ของ $label", $hasJobs, $uid != 5);
    $hasSettings = strpos($json, '/helpdesk-settings') !== false;
    ok("เมนู /helpdesk-settings ของ $label", $hasSettings, $uid == 1);
    $hasCat = strpos($json, '/helpdesk-categories?type=ticketpriority') !== false;
    ok("เมนูหมวดหมู่ของ $label", $hasCat, $uid == 1);
}

echo "--- ทุก endpoint ต้องมี auth ---\n";
$endpoints = [
    ['\Helpdesk\Tickets\Controller', 'index', 'GET'],
    ['\Helpdesk\Jobs\Controller', 'index', 'GET'],
    ['\Helpdesk\Receive\Controller', 'get', 'GET'],
    ['\Helpdesk\Receive\Controller', 'save', 'POST'],
    ['\Helpdesk\Receive\Controller', 'removefile', 'POST'],
    ['\Helpdesk\Detail\Controller', 'get', 'GET'],
    ['\Helpdesk\Detail\Controller', 'reply', 'POST'],
    ['\Helpdesk\Detail\Controller', 'action', 'POST'],
    ['\Helpdesk\Detail\Controller', 'removefile', 'POST'],
    ['\Helpdesk\Categories\Controller', 'get', 'GET'],
    ['\Helpdesk\Categories\Controller', 'save', 'POST'],
    ['\Helpdesk\Settings\Controller', 'get', 'GET'],
    ['\Helpdesk\Settings\Controller', 'save', 'POST'],
    ['\Helpdesk\Dashboard\Controller', 'get', 'GET'],
    ['\Helpdesk\Dashboard\Controller', 'graph', 'GET'],
    ['\Helpdesk\Jobs\Controller', 'action', 'POST']
];
foreach ($endpoints as [$class, $method, $verb]) {
    // ไม่มี token
    unset($_SERVER['HTTP_AUTHORIZATION']);
    $_SERVER['REQUEST_METHOD'] = $verb;
    $csrf = bin2hex(random_bytes(32));
    $_SESSION[$csrf] = ['times' => 0, 'expired' => time() + 3600, 'created' => time()];
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf;
    $req = new \Kotchasan\Http\Request();
    $req = $verb === 'GET' ? $req->withQueryParams([]) : $req->withParsedBody(['action' => 'delete']);
    $obj = new $class();
    $res = json_decode((string) $obj->{$method}($req)->getBody(), true);
    ok("$class::$method ปฏิเสธเมื่อไม่ล็อกอิน", empty($res['success']));
}

echo "--- ทุก POST ต้องมี CSRF ---\n";
$posts = [
    ['\Helpdesk\Receive\Controller', 'save'],
    ['\Helpdesk\Receive\Controller', 'removefile'],
    ['\Helpdesk\Detail\Controller', 'reply'],
    ['\Helpdesk\Detail\Controller', 'action'],
    ['\Helpdesk\Detail\Controller', 'removefile'],
    ['\Helpdesk\Categories\Controller', 'save'],
    ['\Helpdesk\Settings\Controller', 'save'],
    ['\Helpdesk\Jobs\Controller', 'action']
];
foreach ($posts as [$class, $method]) {
    $jwt = \Kotchasan\Jwt::encode(['sub' => 1, 'exp' => time() + 3600], $cfg->jwt_secret);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$jwt;
    $_SERVER['HTTP_X_CSRF_TOKEN'] = str_repeat('a', 64);   // token ปลอม
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $req = (new \Kotchasan\Http\Request())->withParsedBody(['action' => 'delete', 'id' => 1]);
    $obj = new $class();
    $res = json_decode((string) $obj->{$method}($req)->getBody(), true);
    ok("$class::$method ปฏิเสธ CSRF ปลอม", empty($res['success']));
}

echo "--- allowedSortColumns กัน SQL injection ---\n";
$c = new \Helpdesk\Jobs\Controller();
$r = hd_call($c, 'index', hd_request(1, 'GET', ['sort' => 'id; DROP TABLE app_helpdesk--']));
ok('sort อันตรายไม่ทำให้พัง', $r['success']);
$r = hd_call($c, 'index', hd_request(1, 'GET', ['sort' => 'password desc']));
ok('sort คอลัมน์นอกรายการถูกตัดทิ้ง', $r['success']);
ok('ตาราง helpdesk ยังอยู่', \Kotchasan\DB::create()->first('helpdesk', [['id', '>', 0]]) !== false);

summary();
