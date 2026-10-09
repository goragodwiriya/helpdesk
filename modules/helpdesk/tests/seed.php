<?php
/**
 * modules/helpdesk/tests/seed.php
 *
 * ใส่ข้อมูลจำลองของโมดูล helpdesk ลงฐานข้อมูลที่โปรเจกต์นี้ใช้อยู่
 * สร้างสมาชิก (ผู้แจ้ง + เจ้าหน้าที่), Ticket กระจายทุกสถานะ/ความเร่งด่วน/หมวดหมู่,
 * ข้อความตอบกลับทั้งของผู้แจ้งและเจ้าหน้าที่ (มีข้อความภายในด้วย) และงานที่เลยกำหนด
 *
 * วิธีใช้
 *   php modules/helpdesk/tests/seed.php            แสดงสิ่งที่จะทำ (ไม่เขียนจริง)
 *   php modules/helpdesk/tests/seed.php --apply    เขียนข้อมูลจริง
 *   php modules/helpdesk/tests/seed.php --clear    ลบเฉพาะข้อมูลจำลองที่สคริปต์นี้สร้าง
 *
 * ข้อมูลจำลองทุกแถวมีเครื่องหมายกำกับ
 *   - สมาชิก : username ลงท้ายด้วย @helpdesk.demo
 *   - Ticket : ticket_no ขึ้นต้นด้วย DEMO-
 * การลบจึงไม่แตะข้อมูลจริงที่มีอยู่ก่อน
 */
$root = rtrim(getenv('HD_ROOT') ?: dirname(__DIR__, 3), '/').'/';
chdir($root);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
include $root.'load.php';
Kotchasan::createWebApplication('Gcms\Config');

$cfg = \Kotchasan\Config::create();
$db = \Kotchasan\DB::create();
$apply = in_array('--apply', $argv, true);
$clear = in_array('--clear', $argv, true);

const DEMO_MAIL_SUFFIX = '@helpdesk.demo';
const DEMO_TICKET_PREFIX = 'DEMO-';

// ---------------------------------------------------------------- ลบข้อมูลจำลอง
if ($clear) {
    $users = \Kotchasan\Model::createQuery()
        ->select('id')
        ->from('user')
        ->where([['username', 'LIKE', '%'.DEMO_MAIL_SUFFIX]])
        ->fetchAll(true);
    $tickets = \Kotchasan\Model::createQuery()
        ->select('id')
        ->from('helpdesk')
        ->where([['ticket_no', 'LIKE', DEMO_TICKET_PREFIX.'%']])
        ->fetchAll(true);
    $ticketIds = array_map(fn($item) => (int) $item['id'], $tickets);
    $userIds = array_map(fn($item) => (int) $item['id'], $users);

    echo 'จะลบ Ticket จำลอง '.count($ticketIds)." รายการ และสมาชิกจำลอง ".count($userIds)." คน\n";
    if (!$apply) {
        echo "(ยังไม่เขียนจริง ใส่ --apply ด้วยถ้าต้องการลบ)\n";
        exit(0);
    }
    if (!empty($ticketIds)) {
        foreach (\Helpdesk\Detail\Model::statusIdsOf($ticketIds) as $statusIds) {
            foreach ($statusIds as $sid) {
                \Kotchasan\File::removeDirectory(ROOT_PATH.DATA_FOLDER.'helpdesk/'.$sid.'/');
            }
        }
        $db->delete('helpdesk_status', [['helpdesk_id', $ticketIds]], 0);
        $db->delete('helpdesk', [['id', $ticketIds]], 0);
    }
    if (!empty($userIds)) {
        $db->delete('user', [['id', $userIds]], 0);
    }
    echo "ลบข้อมูลจำลองเรียบร้อย\n";
    exit(0);
}

// ---------------------------------------------------------------- สมาชิกจำลอง
$people = [
    ['somchai', 'สมชาย ใจดี', 'helpdesk_agent', '0812345671'],
    ['somying', 'สมหญิง เก่งงาน', 'helpdesk_agent', '0812345672'],
    ['manop', 'มานพ ช่วยเหลือ', 'can_manage_helpdesk,helpdesk_agent', '0812345673'],
    ['pranee', 'ปราณี สุขใจ', '', '0812345674'],
    ['wichai', 'วิชัย ตั้งใจ', '', '0812345675'],
    ['kanya', 'กัญญา พากเพียร', '', '0812345676'],
    ['thanapat', 'ธนพัฒน์ รุ่งเรือง', '', '0812345677'],
    ['nattaya', 'ณัฐธยาน์ ศรีสุข', '', '0812345678']
];

// ---------------------------------------------------------------- Ticket จำลอง
// [หมวดหมู่, ความเร่งด่วน, สถานะ, ผู้แจ้ง(index ใน $people), เจ้าหน้าที่(index|null),
//  วันที่เปิด(กี่วันก่อน), หัวเรื่อง, รายละเอียด, [ข้อความตอบกลับ...]]
// ข้อความตอบกลับ: [ผู้เขียน(index), นาทีหลังเปิดเรื่อง, ข้อความ, private]
$tickets = [
    [1, 4, 1, 3, null, 0, 'ระบบล่มทั้งสำนักงาน เข้าใช้งานไม่ได้เลย',
        "ตั้งแต่เช้าเข้าหน้าเว็บแล้วขึ้น [b]503 Service Unavailable[/b]\nลองแล้วทั้งหมดทุกเครื่องในแผนก",
        []],
    [1, 3, 2, 4, 0, 1, 'ลืมรหัสผ่าน กดขอใหม่แล้วไม่ได้อีเมล',
        "กดปุ่มลืมรหัสผ่านไปหลายรอบแล้ว ไม่มีอีเมลเข้ามาเลยครับ\nตรวจใน junk แล้วด้วย",
        [
            [0, 45, 'รับเรื่องแล้วครับ กำลังตรวจสอบคิวส่งอีเมลของระบบให้', 0],
            [0, 50, 'โน้ตภายใน: คิว SMTP ค้างอยู่ 240 ฉบับ ต้องรีสตาร์ตเซอร์วิส', 1]
        ]],
    [2, 2, 2, 5, 1, 2, 'ติดตั้งโปรแกรมบนเครื่องใหม่ไม่สำเร็จ',
        "ระหว่างติดตั้งขึ้น error code 0x80070643\nดูรายละเอียดได้ที่ https://example.com/install-log.txt",
        [
            [1, 120, 'รบกวนส่งภาพหน้าจอตอนที่ขึ้น error มาให้ดูหน่อยครับ', 0],
            [5, 240, 'แนบมาให้แล้วนะคะ ลองติดตั้งซ้ำอีกรอบก็ยังขึ้นเหมือนเดิม', 0],
            [1, 300, 'ได้รับแล้วครับ เดี๋ยวเข้าไปรีโมตช่วยติดตั้งให้ในช่วงบ่าย', 0]
        ]],
    [3, 1, 3, 6, 1, 4, 'สอบถามวิธีตั้งค่าอีเมลในระบบ',
        'อยากทราบว่าต้องตั้งค่า SMTP ตรงไหน และใช้พอร์ตอะไรครับ',
        [
            [1, 180, "ตั้งได้ที่เมนู ตั้งค่าระบบ > อีเมล ครับ\nใช้พอร์ต 587 แล้วเปิด TLS", 0],
            [6, 400, 'ขอบคุณมากครับ กำลังลองตั้งดู', 0]
        ]],
    [1, 3, 4, 3, 0, 6, 'ปริ้นเตอร์ห้องบัญชีสั่งพิมพ์ไม่ออก',
        'เครื่องขึ้นสถานะ offline ทั้งที่เสียบสายแลนอยู่',
        [
            [0, 90, 'เข้าไปตรวจแล้วครับ IP ชนกับเครื่องอื่น เปลี่ยน IP ให้ใหม่แล้ว', 0],
            [3, 200, 'พิมพ์ได้แล้วค่ะ ขอบคุณมากค่ะ', 0]
        ]],
    [2, 2, 5, 4, 1, 12, 'ขอสิทธิ์เข้าถึงโฟลเดอร์ฝ่ายขาย',
        'ต้องการสิทธิ์อ่านโฟลเดอร์ Sales-2026 ครับ',
        [
            [1, 240, 'เพิ่มสิทธิ์ให้เรียบร้อยแล้วครับ ลองเข้าดูอีกครั้ง', 0],
            [4, 400, 'เข้าได้แล้วครับ ขอบคุณครับ', 0],
            [1, 460, 'ปิดงานนะครับ หากมีปัญหาเพิ่มเติมเปิด Ticket ใหม่ได้เลย', 0]
        ]],
    [3, 1, 5, 7, 0, 20, 'ขอคู่มือการใช้งานระบบฉบับล่าสุด',
        'ขอไฟล์คู่มือ PDF ฉบับปรับปรุงล่าสุดหน่อยค่ะ',
        [
            [0, 300, 'ส่งให้ทางอีเมลแล้วนะครับ', 0]
        ]],
    [1, 4, 6, 5, 2, 8, 'อินเทอร์เน็ตช้ามากในช่วงบ่าย',
        "ช่วง 13:00-15:00 ความเร็วเหลือไม่ถึง 1 Mbps\nแต่ช่วงเช้าปกติดี",
        [
            [2, 300, 'ตรวจแล้วพบว่ามีเครื่องดาวน์โหลดไฟล์ขนาดใหญ่ ได้จำกัดแบนด์วิดท์แล้ว', 0],
            [5, 1400, 'ยังช้าเหมือนเดิมเลยครับ ขอเปิดเรื่องใหม่', 0],
            [2, 1500, 'รับเรื่องต่อครับ จะเข้าไปตรวจสวิตช์ชั้น 3 อีกรอบ', 0]
        ]],
    [2, 3, 7, 6, 2, 15, 'ขอเพิ่มไลเซนส์โปรแกรมออกแบบ 3 ชุด',
        'ฝ่ายออกแบบต้องการเพิ่มไลเซนส์อีก 3 ชุดครับ',
        [
            [2, 600, 'รอฝ่ายจัดซื้ออนุมัติงบก่อนนะครับ ขอพักเรื่องไว้ชั่วคราว', 0],
            [2, 620, 'โน้ตภายใน: ส่งใบเสนอราคาให้จัดซื้อแล้ว รอตอบกลับ', 1]
        ]],
    [1, 2, 1, 7, null, 0, 'จอคอมพิวเตอร์กะพริบเป็นระยะ',
        'จอกะพริบทุก ๆ 10 นาที ลองเปลี่ยนสายแล้วยังเป็นเหมือนเดิม',
        []],
    [3, 2, 2, 3, 1, 3, 'ขอเปลี่ยนอีเมลที่ผูกกับบัญชี',
        'ต้องการเปลี่ยนจากอีเมลเดิมเป็นอีเมลบริษัทค่ะ',
        [
            [1, 200, 'รบกวนยืนยันตัวตนด้วยเลขพนักงานก่อนนะครับ', 0]
        ]],
    [2, 4, 2, 4, 0, 9, 'ไฟล์งานหายจากโฟลเดอร์กลาง',
        "ไฟล์ทั้งโฟลเดอร์หายไปตั้งแต่เมื่อวาน\nเป็นงานที่ต้องส่งลูกค้าสัปดาห์นี้",
        [
            [0, 30, 'กำลังกู้จากไฟล์สำรองของคืนวันก่อนหน้าให้ครับ', 0],
            [0, 60, 'โน้ตภายใน: พบว่าถูกลบโดยบัญชี intern เดี๋ยวแจ้ง HR', 1]
        ]]
];

// วันครบกำหนด: กี่วันหลังเปิดเรื่อง (null = ไม่กำหนด) ทำให้บางรายการเลยกำหนดจริง
$dueOffsets = [1, 3, 3, null, 2, 5, null, 3, 7, 2, null, 1];

echo "ฐานข้อมูล : ".$cfg->web_title." (".(include ROOT_PATH.'settings/database.php')['mysql']['dbname'].")\n";
echo "จะเพิ่ม   : สมาชิก ".count($people)." คน, Ticket ".count($tickets)." รายการ\n";
$replyCount = 0;
foreach ($tickets as $t) {
    $replyCount += count($t[8]);
}
echo "           ข้อความตอบกลับ ".$replyCount." ข้อความ\n\n";

if (!$apply) {
    echo "โหมดแสดงผลอย่างเดียว ยังไม่เขียนลงฐานข้อมูล\n";
    echo "ใส่ --apply ต่อท้ายคำสั่งเพื่อเขียนจริง\n";
    exit(0);
}

// ---------------------------------------------------------------- เขียนสมาชิก
$ids = [];
foreach ($people as $i => [$user, $name, $permission, $phone]) {
    $username = $user.DEMO_MAIL_SUFFIX;
    $found = $db->first('user', [['username', $username]]);
    if ($found) {
        $ids[$i] = (int) $found->id;
        continue;
    }
    $salt = \Kotchasan\Password::uniqid();
    $ids[$i] = $db->insert('user', [
        'username' => $username,
        'salt' => $salt,
        'password' => sha1($cfg->password_key.'demo1234'.$salt),
        'status' => 0,
        'permission' => $permission === '' ? '' : ','.$permission.',',
        'name' => $name,
        'phone' => $phone,
        'created_at' => date('Y-m-d H:i:s', strtotime('-6 month')),
        'active' => 1,
        'social' => 'user'
    ]);
}
echo "สมาชิก : ".count($ids)." คน (รหัสผ่าน demo1234 ทุกคน)\n";

// ---------------------------------------------------------------- เขียน Ticket
$seq = 1;
$made = 0;
foreach ($tickets as $index => [$category, $priority, $status, $reporter, $agent, $daysAgo, $subject, $detail, $replies]) {
    $ticket_no = DEMO_TICKET_PREFIX.sprintf('%04d', $seq++);
    if ($db->first('helpdesk', [['ticket_no', $ticket_no]])) {
        continue;
    }
    $created = date('Y-m-d H:i:s', strtotime('-'.$daysAgo.' day '.rand(8, 16).' hour '.rand(0, 59).' minute'));
    $due = $dueOffsets[$index] === null ? null : date('Y-m-d', strtotime($created.' +'.$dueOffsets[$index].' day'));

    $helpdesk_id = $db->insert('helpdesk', [
        'customer_id' => $ids[$reporter],
        'agent_id' => $agent === null ? 0 : $ids[$agent],
        'ticket_no' => $ticket_no,
        'category' => (string) $category,
        'priority' => (string) $priority,
        'subject' => $subject,
        'detail' => $detail,
        'status' => $status,
        'due_date' => $due,
        'created_at' => $created,
        'updated_at' => $created
    ]);

    // ข้อความแรกคือเนื้อหาของ Ticket เอง (ไฟล์แนบผูกกับ id นี้)
    $db->insert('helpdesk_status', [
        'helpdesk_id' => $helpdesk_id,
        'comment' => '',
        'agent_id' => 0,
        'member_id' => $ids[$reporter],
        'created_at' => $created,
        'private' => 0
    ]);

    $latest = $created;
    foreach ($replies as [$author, $minutes, $comment, $private]) {
        $at = date('Y-m-d H:i:s', strtotime($created.' +'.$minutes.' minute'));
        $isAgent = $people[$author][2] !== '';
        $db->insert('helpdesk_status', [
            'helpdesk_id' => $helpdesk_id,
            'comment' => $comment,
            'agent_id' => $isAgent ? $ids[$author] : 0,
            'member_id' => $isAgent ? 0 : $ids[$author],
            'created_at' => $at,
            'private' => $private
        ]);
        $latest = $at;
    }
    $db->update('helpdesk', [['id', $helpdesk_id]], ['updated_at' => $latest]);
    $made++;
}

echo "Ticket : ".$made." รายการ\n\n";
echo "เข้าระบบด้วยบัญชีจำลองได้ที่\n";
foreach ($people as $i => [$user, $name, $permission]) {
    $role = $permission === '' ? 'ผู้แจ้ง' : (strpos($permission, 'can_manage_helpdesk') !== false ? 'ผู้ดูแลศูนย์ช่วยเหลือ' : 'เจ้าหน้าที่');
    printf("  %-28s %-22s %s\n", $user.DEMO_MAIL_SUFFIX, $name, $role);
}
echo "  รหัสผ่านทุกบัญชี : demo1234\n";
