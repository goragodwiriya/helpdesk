<?php
include getenv('HD_BOOT');
$root = getenv('HD_ROOT');

/**
 * ดึงชื่อฟิลด์ที่เทมเพลตอ้างถึงผ่าน data-* แล้วเทียบกับ response จริง
 *
 * @param string $file
 *
 * @return array
 */
function template_fields($file)
{
    $html = file_get_contents($file);
    $fields = [];
    // data-text / data-html / data-if / data-class / data-attr / data-options-key / data-for
    // data-attr="value:field; href:expr" -> เก็บเฉพาะฝั่งขวาของ :
    preg_match_all('/data-attr="([^"]+)"/', $html, $ma);
    $exprs = [];
    foreach ($ma[1] as $raw) {
        foreach (explode(';', $raw) as $pair) {
            $parts = explode(':', $pair, 2);
            $exprs[] = count($parts) === 2 ? $parts[1] : $parts[0];
        }
    }
    preg_match_all('/data-(?:text|html|if|class)="([^"]+)"/', $html, $m);
    $exprs = array_merge($exprs, $m[1]);
    // data-for="item in list" -> เก็บเฉพาะชื่อ list
    preg_match_all('/data-for="\s*[A-Za-z_][A-Za-z0-9_]*\s+in\s+([^"]+)"/', $html, $mf);
    $exprs = array_merge($exprs, $mf[1]);
    foreach ($exprs as $expr) {
        // ตัดสตริงลิเทอรัลและ {LNG_...} ออกก่อน
        $expr = preg_replace("/'[^']*'/", ' ', $expr);
        $expr = preg_replace('/\{LNG_[^}]*\}/', ' ', $expr);
        preg_match_all('/[A-Za-z_][A-Za-z0-9_.]*/', $expr, $names);
        foreach ($names[0] as $n) {
            $fields[] = $n;
        }
    }
    preg_match_all('/data-(?:options-key|field|files)="([^"]+)"/', $html, $m2);
    foreach ($m2[1] as $n) {
        $fields[] = $n;
    }
    return array_values(array_unique($fields));
}

/**
 * ตรวจว่าชื่อฟิลด์ที่ใช้ในเทมเพลต มีอยู่ใน data ที่ API ส่งมา
 *
 * @param string $label
 * @param string $file
 * @param array $data
 * @param array $options
 * @param array $ignore
 */
function check_fields($label, $file, $data, $options, $ignore = [])
{
    $known = array_merge(array_keys($data), array_keys($options), $ignore, [
        'true', 'false', 'null', 'data', 'options', 'index'
    ]);
    $missing = [];
    foreach (template_fields($file) as $f) {
        $head = explode('.', $f)[0];
        if (in_array($head, $known, true)) {
            continue;
        }
        // ตัวแปรของ data-for เช่น card / reply / file / item
        if (in_array($head, ['card', 'reply', 'file', 'item'], true)) {
            continue;
        }
        $missing[] = $f;
    }
    ok($label.' ใช้เฉพาะฟิลด์ที่มีจริง'.(empty($missing) ? '' : ' ('.implode(', ', $missing).')'), $missing, []);
}

echo "--- receive.html ---\n";
$c = new \Helpdesk\Receive\Controller();
$r = hd_call($c, 'get', hd_request(1, 'GET', ['id' => 0]));
check_fields('receive.html', $root.'/templates/helpdesk/receive.html', $r['data']['data'], $r['data']['options']);

echo "--- detail.html ---\n";
$new = \Kotchasan\Model::createQuery()->select()->from('helpdesk')->orderBy('id', 'desc')->first();
$c = new \Helpdesk\Detail\Controller();
$r = hd_call($c, 'get', hd_request(1, 'GET', ['id' => (int) $new->id]));
check_fields('detail.html', $root.'/templates/helpdesk/detail.html', $r['data']['data'], $r['data']['options']);
$reply = $r['data']['data']['replies'][0] ?? [];
foreach (['id', 'helpdesk_id', 'name', 'comment', 'created_text', 'is_agent', 'is_private', 'can_delete', 'files'] as $k) {
    ok("reply มีฟิลด์ $k", array_key_exists($k, $reply));
}
$file = $r['data']['data']['files'][0] ?? ['url' => '', 'name' => '', 'is_image' => 0];
foreach (['url', 'name', 'is_image'] as $k) {
    ok("file มีฟิลด์ $k", array_key_exists($k, $file));
}

echo "--- settings.html ---\n";
$c = new \Helpdesk\Settings\Controller();
$r = hd_call($c, 'get', hd_request(1));
check_fields('settings.html', $root.'/templates/helpdesk/settings.html', $r['data']['data'], $r['data']['options']);

echo "--- categories.html ---\n";
$c = new \Helpdesk\Categories\Controller();
$r = hd_call($c, 'get', hd_request(1, 'GET', ['type' => 'ticketstatus']));
check_fields('categories.html', $root.'/templates/helpdesk/categories.html', $r['data']['data'], []);

echo "--- dashboard.html ---\n";
$c = new \Helpdesk\Dashboard\Controller();
$r = hd_call($c, 'get', hd_request(1));
check_fields('dashboard.html', $root.'/templates/helpdesk/dashboard.html', ['data' => $r['data']['data']], []);
foreach (['status', 'topic', 'color', 'count', 'url'] as $k) {
    ok("card มีฟิลด์ $k", array_key_exists($k, $r['data']['data']['agent'][0] ?? []));
}
foreach (['topic', 'url'] as $k) {
    ok("shortcut มีฟิลด์ $k", array_key_exists($k, $r['data']['data']['shortcuts'][0] ?? []));
}

echo "--- ตาราง: ฟิลด์ที่ formatter ใช้ ---\n";
$c = new \Helpdesk\Jobs\Controller();
$r = hd_call($c, 'index', hd_request(1));
$row = $r['data']['data'][0];
foreach (['id','ticket_no','customer','avatar','initials','category_text','created_text','updated_text',
          'priority_text','priority_color','agent','attachments','is_overdue','due_text',
          'status_text','status_color','is_closed','can_manage','agent_id'] as $k) {
    ok("แถวตารางมีฟิลด์ $k", array_key_exists($k, $row));
}
echo "--- filters ที่เทมเพลตคาดหวัง ---\n";
foreach (['status','category','priority','agent_id'] as $k) {
    ok("filters มี $k", array_key_exists($k, $r['data']['filters']));
}
summary();
