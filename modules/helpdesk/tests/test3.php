<?php
include getenv('HD_BOOT');
$db = \Kotchasan\DB::create();

echo "--- ลบข้อความตอบกลับ ---\n";
$c = new \Helpdesk\Detail\Controller();
$new = \Kotchasan\Model::createQuery()->select()->from('helpdesk')->orderBy('id', 'desc')->first();
$newId = (int) $new->id;
$replies = \Kotchasan\Model::createQuery()->select('id')->from('helpdesk_status')
    ->where([['helpdesk_id', $newId]])->orderBy('id')->fetchAll(true);
$firstId = (int) $replies[0]['id'];
$lastId = (int) $replies[count($replies) - 1]['id'];

$r = hd_call($c, 'action', hd_request(5, 'POST', ['action' => 'delete', 'helpdesk_id' => $newId, 'id' => $lastId]));
ok('ผู้แจ้งลบข้อความของ agent ไม่ได้', $r['success'], false);
$r = hd_call($c, 'action', hd_request(2, 'POST', ['action' => 'delete', 'helpdesk_id' => $newId, 'id' => $firstId]));
ok('ลบข้อความแรกของ Ticket ไม่ได้', $r['success'], false);
$r = hd_call($c, 'action', hd_request(2, 'POST', ['action' => 'delete', 'helpdesk_id' => 1, 'id' => $lastId]));
ok('ลบข้ามTicket ไม่ได้', $r['success'], false);
$r = hd_call($c, 'action', hd_request(2, 'POST', ['action' => 'delete', 'helpdesk_id' => $newId, 'id' => $lastId]));
ok('เจ้าของข้อความลบได้', $r['success']);
ok('ข้อความถูกลบจริง', $db->first('helpdesk_status', [['id', $lastId]]) === false || $db->first('helpdesk_status', [['id', $lastId]]) === null);

echo "--- reopen ---\n";
$c = new \Helpdesk\Jobs\Controller();
$r = hd_call($c, 'action', hd_request(4, 'POST', ['action' => 'reopen', 'id' => $newId]));
ok('reopen Ticket ที่ยังไม่ปิด = error', $r['success'], false);
// ticket 2 มีสถานะ 5 (Closed) ผู้แจ้งคือ id 4
$r = hd_call($c, 'action', hd_request(5, 'POST', ['action' => 'reopen', 'id' => 2]));
ok('คนที่ไม่เกี่ยวข้อง reopen ไม่ได้', $r['success'], false);
$r = hd_call($c, 'action', hd_request(4, 'POST', ['action' => 'reopen', 'id' => 2]));
ok('ผู้แจ้ง reopen Ticket ของตัวเองได้', $r['success']);
$t2 = $db->first('helpdesk', [['id', 2]]);
ok('สถานะเป็น reopened', (int) $t2->status, (int) $cfg->helpdesk_reopened_status);

echo "--- รับงาน (claim) ---\n";
$db->update('helpdesk', [['id', 3]], ['agent_id' => 0]);
$r = hd_call($c, 'action', hd_request(5, 'POST', ['action' => 'claim', 'id' => 3]));
ok('ผู้ที่ไม่ใช่เจ้าหน้าที่ claim ไม่ได้', $r['success'], false);
$r = hd_call($c, 'action', hd_request(3, 'POST', ['action' => 'claim', 'id' => 3]));
ok('agent2 รับงานได้', $r['success']);
ok('agent_id = 3', (int) $db->first('helpdesk', [['id', 3]])->agent_id, 3);
$r = hd_call($c, 'action', hd_request(2, 'POST', ['action' => 'claim', 'id' => 3]));
ok('agent อื่นแย่งงานไม่ได้', $r['success'], false);
$r = hd_call($c, 'action', hd_request(1, 'POST', ['action' => 'claim', 'id' => 3]));
ok('ผู้ดูแลย้ายงานได้', $r['success']);

echo "--- ตั้งค่าโมดูล ---\n";
$c = new \Helpdesk\Settings\Controller();
$r = hd_call($c, 'get', hd_request(4));
ok('ผู้ใช้ทั่วไปอ่านการตั้งค่าไม่ได้', $r['success'], false);
$r = hd_call($c, 'get', hd_request(1));
ok('แอดมินอ่านได้', $r['success']);
ok('มี helpdesk_reopened_status', isset($r['data']['data']['helpdesk_reopened_status']));
$r = hd_call($c, 'save', hd_request(1, 'POST', [
    'helpdesk_first_status' => 1, 'helpdesk_closed_status' => 1, 'helpdesk_reopened_status' => 6,
    'helpdesk_prefix' => 'T%Y%M-', 'helpdesk_no' => '%04d', 'helpdesk_w' => 800, 'helpdesk_sla_days' => 3
]));
ok('สถานะเริ่มต้นซ้ำกับสถานะปิด = error', $r['success'], false);
$r = hd_call($c, 'save', hd_request(1, 'POST', [
    'helpdesk_first_status' => 1, 'helpdesk_closed_status' => 5, 'helpdesk_reopened_status' => 7,
    'helpdesk_prefix' => 'T%Y%M-', 'helpdesk_no' => '%04d', 'helpdesk_w' => 800, 'helpdesk_sla_days' => 3,
    'helpdesk_mail_user' => 'สวัสดีครับ'
]));
ok('บันทึกการตั้งค่าได้', $r['success']);
$saved = include getenv('HD_ROOT').'/settings/config.php';
ok('helpdesk_reopened_status ถูกบันทึกจริง', $saved['helpdesk_reopened_status'], 7);
ok('helpdesk_sla_days ถูกบันทึก', $saved['helpdesk_sla_days'], 3);
ok('helpdesk_mail_user ถูกบันทึก', $saved['helpdesk_mail_user'], 'สวัสดีครับ');

echo "--- หมวดหมู่ / สถานะ ---\n";
$c = new \Helpdesk\Categories\Controller();
$r = hd_call($c, 'get', hd_request(4, 'GET', ['type' => 'ticketstatus']));
ok('ผู้ใช้ทั่วไปอ่านไม่ได้', $r['success'], false);
$r = hd_call($c, 'get', hd_request(1, 'GET', ['type' => 'ticketstatus']));
ok('อ่าน ticketstatus ได้', $r['success']);
ok('มี 7 แถว', count($r['data']['data']['options']['data']), 7);
ok('มีคอลัมน์สี', count($r['data']['data']['options']['columns']), 4);
$r = hd_call($c, 'get', hd_request(1, 'GET', ['type' => 'category']));
ok('หมวดหมู่ไม่มีคอลัมน์สี', count($r['data']['data']['options']['columns']), 3);
$r = hd_call($c, 'get', hd_request(1, 'GET', ['type' => 'xxx']));
ok('type ที่ไม่รู้จักถูกแทนด้วย ticketstatus', $r['data']['data']['type'], 'ticketstatus');

$r = hd_call($c, 'save', hd_request(1, 'POST', [
    'type' => 'ticketpriority',
    'category_id' => ['1', '2', '2'],
    'topic' => ['ต่ำ', 'กลาง', 'ซ้ำ'],
    'color' => ['#111111', '#222222', '#333333'],
    'is_active' => [1, 1, 1]
]));
ok('ID ซ้ำ = error', $r['success'], false);
$r = hd_call($c, 'save', hd_request(1, 'POST', [
    'type' => 'ticketpriority',
    'category_id' => ['1', '2', '3', '4'],
    'topic' => ['ต่ำ', 'ปานกลาง', 'ด่วน', 'ด่วนมาก'],
    'color' => ['#33691E', '#4A148C', '#FF6F00', '#B71C1C'],
    'is_active' => [1, 1, 1, 0]
]));
ok('บันทึกได้', $r['success']);
$prio = \Helpdesk\Category\Model::all('ticketpriority', false);
ok('ยังมี 4 แถว', count($prio), 4);
ok('แถวที่ 4 ปิดใช้งาน', (int) $prio[3]['is_active'], 0);
ok('สีถูกบันทึก', $prio[2]['color'], '#FF6F00');
ok('toOptions เอาเฉพาะที่เปิดใช้งาน', count(\Helpdesk\Category\Model::toOptions('ticketpriority')), 3);

echo "--- dashboard ---\n";
$c = new \Helpdesk\Dashboard\Controller();
$r = hd_call($c, 'get', hd_request(5));
ok('ผู้ใช้ทั่วไปเปิด dashboard ได้', $r['success']);
ok('ไม่ใช่เจ้าหน้าที่', $r['data']['data']['is_agent'], 0);
ok('การ์ดเจ้าหน้าที่ว่าง', count($r['data']['data']['agent']), 0);
ok('มีเมนูลัดตามหมวดหมู่', count($r['data']['data']['shortcuts']) > 0);
$r = hd_call($c, 'get', hd_request(1));
ok('แอดมินเป็นเจ้าหน้าที่', $r['data']['data']['is_agent'], 1);
ok('แอดมินเห็นยอดรวมทั้งระบบ', $r['data']['data']['agent_total'] >= 5);
$r = hd_call($c, 'graph', hd_request(1, 'GET', ['months' => 6]));
ok('กราฟ success', $r['success']);
// GraphRenderer::setData() รับเฉพาะ array ของ series [{name, data:[{label, value}]}]
// รูปแบบ {labels, datasets} แบบ Chart.js จะทำให้ validateData() โยน error แล้วกราฟไม่ขึ้น
$series = $r['data'];
ok('กราฟคืนค่าเป็น array ของ series', is_array($series) && array_is_list($series) && count($series) === 1);
ok('series มี name', !empty($series[0]['name']));
ok('series มี data 6 จุด', count($series[0]['data']), 6);
$badPoints = 0;
foreach ($series[0]['data'] as $point) {
    if (!array_key_exists('label', $point) || !array_key_exists('value', $point)) {
        $badPoints++;
    }
}
ok('ทุกจุดมี label และ value', $badPoints, 0);
ok('ไม่มีคีย์ labels/datasets แบบ Chart.js', isset($r['data']['labels']) || isset($r['data']['datasets']), false);
$r = hd_call($c, 'graph', hd_request(5, 'GET', ['months' => 6]));
ok('ผู้ใช้ทั่วไปดูกราฟไม่ได้', $r['success'], false);

summary();
