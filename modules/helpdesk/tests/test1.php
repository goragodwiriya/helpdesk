<?php
include getenv('HD_BOOT');
echo "--- อ่านข้อมูลพื้นฐาน ---\n";
$statuses = \Helpdesk\Category\Model::all('ticketstatus', false);
ok('ticketstatus มี 7 รายการ', count($statuses), 7);
$map = \Helpdesk\Category\Model::map('ticketstatus');
ok('สถานะ 5 คือ Closed', \Helpdesk\Category\Model::topicOf($map, 5), 'Closed');
ok('สีของสถานะ 5', \Helpdesk\Category\Model::colorOf($map, 5), '#FF0000');
$agents = \Helpdesk\Agent\Model::map();
ok('มี agent 3 คน (agent1, agent2, แอดมิน)', count($agents), 3);

echo "--- ตาราง Ticket ของฉัน ---\n";
$c = new \Helpdesk\Tickets\Controller();
$r = hd_call($c, 'index', hd_request(4));
ok('tickets success', $r['success']);
ok('ลูกค้า ก มี 2 ticket', $r['data']['meta']['total'], 2);
$row = $r['data']['data'][0];
ok('มี status_text', isset($row['status_text']));
ok('มี priority_color', isset($row['priority_color']));
ok('มี category_text', isset($row['category_text']));

echo "--- ตาราง Ticket เจ้าหน้าที่ ---\n";
$c = new \Helpdesk\Jobs\Controller();
$r = hd_call($c, 'index', hd_request(1));
ok('jobs (admin) success', $r['success']);
ok('แอดมินเห็นทั้ง 4 ticket', $r['data']['meta']['total'], 4);
$r = hd_call($c, 'index', hd_request(2));
ok('agent1 เห็น 2 ticket (id 1, 4)', $r['data']['meta']['total'], 2);
$r = hd_call($c, 'index', hd_request(3));
ok('agent2 เห็น 1 ticket (id 2)', $r['data']['meta']['total'], 1);
$r = hd_call($c, 'index', hd_request(5));
ok('ลูกค้าเข้าหน้าเจ้าหน้าที่ไม่ได้', $r['success'], false);

echo "--- ตัวกรอง ---\n";
$c = new \Helpdesk\Jobs\Controller();
$r = hd_call($c, 'index', hd_request(1, 'GET', ['status' => 5]));
ok('กรองสถานะ Closed ได้ 1 รายการ', $r['data']['meta']['total'], 1);
$r = hd_call($c, 'index', hd_request(1, 'GET', ['category' => 1]));
ok('กรองหมวดหมู่ 1 ได้ 2 รายการ', $r['data']['meta']['total'], 2);
$r = hd_call($c, 'index', hd_request(1, 'GET', ['agent_id' => -1]));
ok('งานที่ยังไม่มีผู้รับผิดชอบ 1 รายการ', $r['data']['meta']['total'], 1);
$r = hd_call($c, 'index', hd_request(1, 'GET', ['search' => 'เข้าระบบ']));
ok('ค้นหา "เข้าระบบ" เจอ 1 รายการ', $r['data']['meta']['total'], 1);
$r = hd_call($c, 'index', hd_request(1, 'GET', ['search' => 'ลูกค้า ข']));
ok('ค้นหาจากชื่อลูกค้าเจอ 2 รายการ', $r['data']['meta']['total'], 2);
$r = hd_call($c, 'index', hd_request(1, 'GET', ['from' => '2024-03-01']));
ok('กรองวันที่ตั้งแต่ 2024-03-01 ได้ 2 รายการ', $r['data']['meta']['total'], 2);

echo "--- filters/options ---\n";
$r = hd_call($c, 'index', hd_request(1));
ok('filters มี agent_id (admin)', isset($r['data']['filters']['agent_id']));
$r = hd_call($c, 'index', hd_request(2));
ok('agent ไม่เห็น filter agent_id', isset($r['data']['filters']['agent_id']), false);

summary();
