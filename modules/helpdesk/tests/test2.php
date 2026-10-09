<?php
include getenv('HD_BOOT');
$db = \Kotchasan\DB::create();

echo "--- ฟอร์มเปิด Ticket ---\n";
$c = new \Helpdesk\Receive\Controller();
$r = hd_call($c, 'get', hd_request(5, 'GET', ['id' => 0, 'category' => '2']));
ok('receive/get success', $r['success']);
ok('รายการใหม่ id=0', $r['data']['data']['id'], 0);
ok('category ตั้งต้นมาจาก query', $r['data']['data']['category'], '2');
ok('options category มี 3 ตัว', count($r['data']['options']['category']), 3);
ok('options priority มี 4 ตัว', count($r['data']['options']['priority']), 4);

echo "--- validate ---\n";
$r = hd_call($c, 'save', hd_request(5, 'POST', ['id' => 0]));
ok('ฟอร์มว่าง = error', $r['success'], false);
ok('แจ้ง subject', isset($r['errors']['subject']) || isset($r['data']['errors']['subject']));

echo "--- บันทึก Ticket ใหม่ ---\n";
$r = hd_call($c, 'save', hd_request(5, 'POST', [
    'id' => 0, 'category' => '1', 'priority' => '3',
    'subject' => 'ทดสอบเปิด Ticket ใหม่', 'detail' => 'รายละเอียด [b]ตัวหนา[/b] http://example.com'
]));
ok('save success', $r['success']);
$new = \Kotchasan\Model::createQuery()->select()->from('helpdesk')->orderBy('id', 'desc')->first();
ok('ticket ใหม่ถูกบันทึก', $new->subject, 'ทดสอบเปิด Ticket ใหม่');
ok('customer_id = 5', (int) $new->customer_id, 5);
ok('status = first_status', (int) $new->status, (int) $cfg->helpdesk_first_status);
ok('agent_id = 0', (int) $new->agent_id, 0);
ok('มี ticket_no', !empty($new->ticket_no));
$prefix = \Kotchasan\Number::printf($cfg->helpdesk_prefix, 0);
ok('ticket_no ขึ้นต้นด้วย prefix ที่ตั้งไว้ ('.$prefix.')', strpos($new->ticket_no, $prefix) === 0);
ok('running number ถูกบันทึกลงตาราง number', \Kotchasan\DB::create()->first('number', [['prefix', $prefix]]) !== false);
$firstMsg = \Kotchasan\Model::createQuery()->select()->from('helpdesk_status')->where([['helpdesk_id', (int) $new->id]])->first();
ok('มีข้อความแรกของ ticket', $firstMsg !== null && $firstMsg !== false);
ok('ข้อความแรก member_id = 5', (int) $firstMsg->member_id, 5);

$newId = (int) $new->id;

echo "--- รายละเอียด Ticket ---\n";
$c = new \Helpdesk\Detail\Controller();
$r = hd_call($c, 'get', hd_request(5, 'GET', ['id' => $newId]));
ok('detail/get success', $r['success']);
ok('BBCode ถูกแปลง', strpos($r['data']['data']['detail'], '<b>ตัวหนา</b>') !== false);
ok('ลิงก์ถูกแปลง', strpos($r['data']['data']['detail'], '<a href="http://example.com"') !== false);
ok('เจ้าของแก้ไขได้ (ยังไม่มีคนตอบ)', $r['data']['data']['can_edit'], 1);
ok('ผู้แจ้งเลือกสถานะได้ 2 ตัว', count($r['data']['options']['status']), 2);
$r = hd_call($c, 'get', hd_request(4, 'GET', ['id' => $newId]));
ok('คนอื่นเปิดดูไม่ได้', $r['success'], false);
$r = hd_call($c, 'get', hd_request(2, 'GET', ['id' => $newId]));
ok('agent เปิดดูได้', $r['success']);
ok('agent เลือกสถานะได้ครบ 7', count($r['data']['options']['status']), 7);

echo "--- ตอบกลับ ---\n";
$r = hd_call($c, 'reply', hd_request(2, 'POST', ['helpdesk_id' => $newId, 'comment' => '', 'status' => 2]));
ok('ตอบว่างไม่ได้', $r['success'], false);
$r = hd_call($c, 'reply', hd_request(2, 'POST', [
    'helpdesk_id' => $newId, 'comment' => 'รับเรื่องแล้ว', 'status' => 2
]));
ok('agent ตอบได้', $r['success']);
$after = $db->first('helpdesk', [['id', $newId]]);
ok('สถานะเปลี่ยนเป็น 2', (int) $after->status, 2);
ok('agent_id ถูกกำหนดอัตโนมัติ', (int) $after->agent_id, 2);
ok('updated_at ไม่เก่ากว่า created_at', $after->updated_at >= $after->created_at);

echo "--- private reply ---\n";
$r = hd_call($c, 'reply', hd_request(2, 'POST', [
    'helpdesk_id' => $newId, 'comment' => 'โน้ตภายใน', 'status' => 2, 'private' => 1
]));
ok('บันทึก private ได้', $r['success']);
$r = hd_call($c, 'get', hd_request(5, 'GET', ['id' => $newId]));
ok('ผู้แจ้งไม่เห็นข้อความ private', $r['data']['data']['reply_count'], 1);
$r = hd_call($c, 'get', hd_request(2, 'GET', ['id' => $newId]));
ok('agent เห็นครบ 2 ข้อความ', $r['data']['data']['reply_count'], 2);
ok('เจ้าของแก้ไขไม่ได้แล้ว (มีคนตอบ)', $r['data']['data']['can_edit'], 1);
$r = hd_call($c, 'get', hd_request(5, 'GET', ['id' => $newId]));
ok('ผู้แจ้งแก้ไข Ticket ไม่ได้แล้ว', $r['data']['data']['can_edit'], 0);

summary();
