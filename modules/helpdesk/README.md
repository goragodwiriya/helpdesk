# โมดูล helpdesk (ศูนย์ช่วยเหลือ)

พอร์ตจาก `/mnt/Server/htdocs/now.js/helpdesk/helpdesk/modules/helpdesk`
(Kotchasan 6.x render ฝั่ง server, DataTable + View classes, 3,708 บรรทัด)
มาเป็นโมดูล pluggable ของ Now.js — ทำงานเหมือนเดิมครบทุกฟีเจอร์ และใช้ข้อมูลเดิมได้ทันที

ตรวจด้วย `php modules/helpdesk/tests/run.php` — 192 assertion แบบ end-to-end
ชุดทดสอบสร้างฐานข้อมูลของตัวเองจากสคีมาและข้อมูลของระบบเดิม รัน `install/upgrade2.php` ของจริง
แล้วจึงเรียก Controller ทั้งหมด จบแล้วลบฐานทดสอบทิ้ง (ไม่แตะฐานข้อมูลจริง)

## แผนที่ระบบเดิม → ระบบใหม่

| เดิม (`module=`) | route | API | ชนิด |
|---|---|---|---|
| `Home\Controller::addBlock` | `/` | `api/helpdesk/dashboard/get` · `/graph` | `\Gcms\Api` + `data-component="api"` |
| `helpdesk-history` | `/helpdesk` | `api/helpdesk/tickets` | `\Gcms\Table` |
| `helpdesk-setup` | `/helpdesk-jobs` | `api/helpdesk/jobs` + `/action` + `/export` | `\Gcms\Table` |
| `helpdesk-receive` | `/helpdesk-receive?id=` | `api/helpdesk/receive/get\|save\|removefile` | `\Gcms\Api` + `data-form` |
| `helpdesk-detail` | `/helpdesk-detail?id=` | `api/helpdesk/detail/get\|reply\|action\|removefile` | `\Gcms\Api` + `data-form` |
| `helpdesk-settings` | `/helpdesk-settings` | `api/helpdesk/settings/get\|save` | `\Gcms\Api` |
| `helpdesk-categories&type=` `helpdesk-statuses&type=` | `/helpdesk-categories?type=` | `api/helpdesk/categories/get\|save` | `\Gcms\Api` + `data-editable-rows` |

ระบบเดิมแยกหน้าหมวดหมู่กับหน้าสถานะเป็นสองชุดคนละแบบ (ตารางแก้รวด กับ ตาราง+modal)
ทั้งที่เก็บใน `category` ตารางเดียวกัน จึงรวมเป็นชุดเดียว 3 เมนูแยกด้วย `?type=`

## ฐานข้อมูล

| ตาราง | หมายเหตุ |
|---|---|
| `{prefix}_helpdesk` | Ticket · เดิม `create_date` → `created_at` · เพิ่ม `agent_id` `updated_at` `due_date` · `detail` เป็น TEXT |
| `{prefix}_helpdesk_status` | ข้อความตอบกลับ แถวแรกของแต่ละ Ticket คือเนื้อหาที่ผู้แจ้งเปิดเรื่องไว้ · `comment` เป็น TEXT |
| `{prefix}_category` | `ticketstatus` `ticketpriority` `category` · `published` → `is_active` (base จัดการให้) |
| `{prefix}_number` | running number ของ `ticket_no` · เดิม `last_update` → `updated_at` และตัดคอลัมน์ `key` |

ไฟล์แนบยังอยู่ที่ `datas/helpdesk/<helpdesk_status.id>/` เหมือนเดิมทุกประการ
ไฟล์เดิมจึงแสดงได้ทันทีโดยไม่ต้องย้าย

**สิทธิ์ใช้ชื่อเดิม** `can_manage_helpdesk` และ `helpdesk_agent` ผู้ใช้เดิมไม่ต้องตั้งค่าใหม่

## ค่ากำหนด (`settings/config.php`)

`helpdesk_first_status` `helpdesk_closed_status` `helpdesk_reopened_status`
`helpdesk_prefix` `helpdesk_no` `helpdesk_w` `helpdesk_img_typies`
`helpdesk_sla_days` `helpdesk_mail_user` `helpdesk_mail_agent`

ประกาศไว้ 3 ที่ตามกฎ: `Gcms/Config.php` · `install/settings/config.php` · `install/upgrade2.php`

## บั๊กของระบบเดิมที่แก้ไปพร้อมกัน

1. `models/receive.php` — `Model::get($id)` ตอนแก้ไขลืม `->first()` คืน QueryBuilder ทำให้ "แก้ไข Ticket" กลายเป็นสร้างใหม่
2. `models/settings.php` — ฟอร์มมีช่อง "สถานะเมื่อเปิดใหม่" แต่ `submit()` ไม่บันทึก `helpdesk_reopened_status`
3. `models/detail.php` — ลบ reply ได้โดยไม่ตรวจว่าอยู่ใน Ticket ไหน และไม่ตรวจความเป็นเจ้าของ
4. `models/setup.php` — `reopen` ไม่ตรวจว่า Ticket ปิดอยู่จริงหรือไม่
5. `models/statuses.php` vs `controllers/statuses.php` — หน้าเพจเช็ค `can_manage_helpdesk` แต่ action เช็ค `can_config`
6. `views/detail.php` — ปุ่ม Reopen แสดงให้ผู้แจ้งเห็น แต่ endpoint ต้องมีสิทธิ์ Agent ผู้แจ้งกดแล้วไม่ทำงาน
7. `models/statuses.php` — `all()`/`toSelect()` อ้างคอลัมน์ `status_id` ที่ไม่มีในตาราง `category`
8. `views/setup.php` `views/history.php` — `$img` อาจไม่ถูกกำหนดถ้าชื่อผู้แจ้งว่าง
9. `models/categories.php` — บันทึกหมวดหมู่แบบ DELETE แล้ว INSERT ทำให้ `color`/`published` ถูกรีเซ็ต

## ฟีเจอร์ที่เพิ่มจากระบบเดิม

- `agent_id` เป็นเจ้าของงานจริง (เดิมอนุมานจากการตอบ ทำให้ต้อง JOIN + GROUP_CONCAT ทุก query)
  พร้อมปุ่ม "รับงาน" และการมอบหมายจากหน้ารายละเอียด
- `updated_at` แทนการคำนวณ `MAX(created_at)` ของตารางข้อความทุกครั้ง
- `due_date` (SLA) + ไฮไลต์งานเกินกำหนด + ตัวกรอง `overdue`
- ผู้แจ้งเปิด Ticket ของตัวเองใหม่ได้ (แก้บั๊กข้อ 6)
- Export CSV ของรายการ Ticket
- Dashboard หน้าแรก + กราฟ Ticket รายเดือน
- ค้นหาจากชื่อผู้แจ้งได้ในหน้าเจ้าหน้าที่
- อีเมลแจ้งเตือนเป็น HTML (`views/email.html`) ข้อความเกริ่นนำแก้ไขได้จากหน้าตั้งค่า
- แสดงจำนวนไฟล์แนบในรายการ Ticket

## ข้อควรรู้

- `Gcms\EmailTemplate` ของ base ยังใช้ไม่ได้ — อ้างตาราง `emailtemplate` ที่ไม่มีในตัวติดตั้ง
  และ `modules/index/models/email.php` เรียก `EmailTemplate::register()` กับ `send()` 4 พารามิเตอร์
  ซึ่งไม่มีจริงในคลาส โมดูลนี้จึงเรนเดอร์อีเมลเองจาก `views/email.html`
- `\Kotchasan\Model` มีเมธอด `insert/update/delete/select` แบบ non-static
  ห้ามตั้งชื่อเมธอด static ในโมเดลซ้ำกับชื่อเหล่านี้ (PHP fatal ทันที)
- `QueryBuilder::join()` รับ `(table, condition, type)` ไม่ใช่ `(table, type, condition)` แบบ Kotchasan รุ่นเก่า
