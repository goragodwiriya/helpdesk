# Helpdesk

ระบบศูนย์ช่วยเหลือ (Helpdesk / Support Center) สำหรับรับแจ้งปัญหาและติดตามงานแบบ Ticket
สร้างบน [Now.js](https://github.com/goragodwiriya/nowjs) + [Kotchasan](https://github.com/goragodwiriya/kotchasan) (PHP) ติดตั้งผ่านเว็บได้ภายในไม่กี่ขั้นตอน

> **English:** A ticket-based helpdesk / support center built with Now.js (frontend) and Kotchasan (PHP backend).
> Web installer, role-based access (member / agent / admin), SLA due dates, email notifications, CSV export and PWA support.
> The UI is Thai-first with an English language pack.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-777bb4.svg)
![Version](https://img.shields.io/badge/version-7.0.4-green.svg)

## ฟีเจอร์

**ผู้แจ้ง (สมาชิก)**
- เปิด Ticket พร้อมแนบไฟล์/รูปภาพ (ชนิดไฟล์และขนาดตั้งค่าได้)
- ติดตามสถานะ ตอบกลับ และเปิด Ticket ของตัวเองใหม่ได้
- รับอีเมลแจ้งเตือนเมื่อมีความคืบหน้า

**เจ้าหน้าที่ (Agent) / ผู้ดูแลระบบ**
- รายการงานทั้งหมด พร้อมค้นหา (รวมชื่อผู้แจ้ง) และตัวกรองสถานะ/หมวดหมู่/ความสำคัญ/งานเกินกำหนด
- ปุ่ม "รับงาน" และการมอบหมายงานให้ Agent จากหน้ารายละเอียด
- SLA: กำหนดจำนวนวันตอบสนอง ไฮไลต์งานเกินกำหนด
- Dashboard หน้าแรก และกราฟ Ticket รายเดือน
- Export รายการ Ticket เป็น CSV
- จัดการหมวดหมู่ สถานะ และระดับความสำคัญของ Ticket (กำหนดสีได้)
- อีเมลแจ้งเตือนแบบ HTML ปรับข้อความเกริ่นนำได้จากหน้าตั้งค่า

**ระบบพื้นฐาน**
- ระบบสมาชิก/สิทธิ์ (สมาชิก, ผู้ดูแลระบบ, หัวหน้าแผนก, Agent) พร้อมหน้าเข้าสู่ระบบ ลืมรหัสผ่าน และยืนยันบัญชี
- หลายภาษา (ไทย/อังกฤษ) จัดการภาษาได้จากหน้าผู้ดูแล
- โมดูลดาวน์โหลดไฟล์ และ Timeline
- รองรับ PWA (`manifest.json` + `service-worker.js`) ใช้งานบนมือถือได้
- มี endpoint สำหรับเชื่อมต่อ LINE และ Telegram (`line/`, `telegram/`) และ SMS (`Thaibluksms/`)

## ความต้องการของระบบ

- PHP 7.4 ขึ้นไป พร้อม extension: `pdo_mysql`, `mbstring`, `zlib`, `xml`, `gd`, `curl`
- MySQL / MariaDB (InnoDB, utf8mb4)
- Apache (มี `.htaccess` ให้) หรือเว็บเซิร์ฟเวอร์อื่นที่ตั้งค่า rewrite เทียบเท่า
- Node.js — **เฉพาะ** กรณีต้องการ build ฝั่ง JavaScript เองหรือรันเทสต์ (ไม่จำเป็นต่อการใช้งานทั่วไป)

## การติดตั้ง

1. ดาวน์โหลดหรือ clone โปรเจ็คไปไว้ใน web root

   ```bash
   git clone https://github.com/goragodwiriya/helpdesk.git
   cd helpdesk
   ```

2. สร้างฐานข้อมูลเปล่า (collation `utf8mb4_unicode_ci`)
3. ให้สิทธิ์เขียนกับโฟลเดอร์ `settings/` และ `datas/`

   ```bash
   chmod -R 775 settings datas
   ```

4. เปิด `http://your-host/install/` ในเบราว์เซอร์ แล้วทำตามขั้นตอน
   ตรวจความพร้อมของระบบ → ตั้งค่าฐานข้อมูล → สร้างผู้ดูแลระบบ → เสร็จสิ้น
5. **หลังติดตั้งเสร็จ ควรลบหรือจำกัดการเข้าถึงโฟลเดอร์ `install/`**

### อัปเกรด

วางไฟล์เวอร์ชันใหม่ทับ (เก็บ `settings/` และ `datas/` ไว้) แล้วเปิด `/install/` อีกครั้ง
ตัวติดตั้งจะตรวจเวอร์ชันและปรับโครงสร้างฐานข้อมูลให้อัตโนมัติ

มีสคริปต์ CLI สำหรับงานดูแลระบบใน `install/` เช่น `cli-upgrade.php`, `cli-verify.php`, `cli-language.php`

## การตั้งค่า

ตั้งค่าส่วนใหญ่ได้จากหน้าเว็บ (เมนูตั้งค่า) ค่าที่เกี่ยวกับ Helpdesk เก็บใน `settings/config.php`:

| คีย์ | ความหมาย |
|---|---|
| `helpdesk_prefix` / `helpdesk_no` | รูปแบบเลข Ticket (เช่น `%Y%M` + `%03d` → `202610001`) |
| `helpdesk_first_status` | สถานะเริ่มต้นของ Ticket ใหม่ |
| `helpdesk_closed_status` / `helpdesk_reopened_status` | สถานะเมื่อปิดงาน / เปิดงานใหม่ |
| `helpdesk_sla_days` | จำนวนวัน SLA (0 = ไม่ใช้) |
| `helpdesk_img_typies` | ชนิดไฟล์ภาพที่อนุญาต |
| `helpdesk_w` | ความกว้างภาพที่ย่อเมื่ออัปโหลด |
| `helpdesk_mail_user` / `helpdesk_mail_agent` | ข้อความเกริ่นนำอีเมลถึงผู้แจ้ง / เจ้าหน้าที่ |

> `settings/config.php` และ `settings/database.php` มีรหัสลับของระบบ **ห้าม commit ขึ้น repository สาธารณะ**
> ตรวจ `.gitignore` ให้แน่ใจก่อนเผยแพร่ fork ของคุณ

## โครงสร้างโปรเจ็ค

```
.
├── index.php, api.php, export.php, load.php   # entry points
├── Gcms/                  # คลาสพื้นฐานของแอป (Api, Table, Config, ...)
├── Kotchasan/             # PHP framework
├── Now/                   # Now.js frontend (js/css/dist)
├── modules/
│   ├── helpdesk/          # โมดูลหลัก: controllers, models, views, tests
│   ├── index/             # สมาชิก ผู้ใช้ ตั้งค่า ภาษา เมนู
│   ├── download/          # ดาวน์โหลดไฟล์
│   ├── export/            # ส่งออกข้อมูล
│   └── timeline/          # Timeline
├── templates/             # HTML templates
├── language/              # ไฟล์ภาษา (th/en)
├── install/               # ตัวติดตั้ง/อัปเกรด + SQL schema
├── settings/              # ค่ากำหนด (สร้างตอนติดตั้ง)
├── datas/                 # ไฟล์ที่ผู้ใช้อัปโหลด (datas/helpdesk/<id>/)
├── line/ telegram/ Thaibluksms/   # ตัวเชื่อมต่อภายนอก
└── manifest.json, service-worker.js   # PWA
```

รายละเอียดเชิงลึกของโมดูล Helpdesk (route, API, ตาราง, บั๊กที่แก้) อยู่ที่ [modules/helpdesk/README.md](modules/helpdesk/README.md)

## ฐานข้อมูล

ตารางหลักของ Helpdesk (ใช้ prefix ที่กำหนดตอนติดตั้ง):

| ตาราง | ใช้เก็บ |
|---|---|
| `{prefix}_helpdesk` | Ticket |
| `{prefix}_helpdesk_status` | ข้อความตอบกลับของแต่ละ Ticket |
| `{prefix}_category` | หมวดหมู่ สถานะ และระดับความสำคัญ |
| `{prefix}_number` | running number ของเลข Ticket |
| `{prefix}_user` | สมาชิก |

## การพัฒนาและทดสอบ

ชุดทดสอบของโมดูล Helpdesk เป็นแบบ end-to-end ฝั่ง PHP สร้างฐานข้อมูลชั่วคราวของตัวเองแล้วลบทิ้งเมื่อจบ (ไม่แตะฐานข้อมูลจริง)

```bash
php modules/helpdesk/tests/run.php
```

ฝั่ง JavaScript (Now.js):

```bash
npm install
npm run build        # build bundles ทั้งหมด
npm test             # vitest
```

## สิทธิ์การใช้งาน

เผยแพร่ภายใต้สัญญาอนุญาต [MIT](LICENSE) © 2026 Goragod Wiriya

## เครดิต

- [Now.js](https://github.com/goragodwiriya/nowjs) — JavaScript framework
- [Kotchasan](https://github.com/goragodwiriya/kotchasan) — PHP framework
