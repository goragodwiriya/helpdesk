/**
 * modules/helpdesk/admin.js
 *
 * ลงทะเบียน route และ formatter ของโมดูลศูนย์ช่วยเหลือ
 */
EventManager.on('router:initialized', () => {
  // หน้าแรกของระบบคือสรุป Ticket
  RouterManager.register('/', {
    template: 'helpdesk/dashboard.html',
    title: '{LNG_Helpdesk}',
    requireAuth: true
  });

  RouterManager.register('/helpdesk', {
    template: 'helpdesk/tickets.html',
    title: '{LNG_My tickets}',
    requireAuth: true
  });

  RouterManager.register('/helpdesk-jobs', {
    template: 'helpdesk/jobs.html',
    title: '{LNG_Tickets} ({LNG_Agent})',
    requireAuth: true
  });

  RouterManager.register('/helpdesk-receive', {
    template: 'helpdesk/receive.html',
    title: '{LNG_Create ticket}',
    menuPath: '/helpdesk',
    requireAuth: true
  });

  RouterManager.register('/helpdesk-detail', {
    template: 'helpdesk/detail.html',
    title: '{LNG_View} {LNG_Ticket}',
    menuPath: '/helpdesk',
    requireAuth: true
  });

  RouterManager.register('/helpdesk-categories', {
    template: 'helpdesk/categories.html',
    title: '{LNG_Helpdesk}',
    requireAuth: true
  });

  RouterManager.register('/helpdesk-settings', {
    template: 'helpdesk/settings.html',
    title: '{LNG_Module settings} {LNG_Helpdesk}',
    requireAuth: true
  });
});

/**
 * คอลัมน์หัวเรื่องของตาราง Ticket
 * รวมรูปผู้แจ้ง เลขที่ หัวเรื่อง หมวดหมู่ ความเร่งด่วน และผู้รับผิดชอบไว้ในช่องเดียว
 * เหมือนหน้าตารางของระบบเดิม
 *
 * @param {HTMLElement} cell
 * @param {string} rawValue
 * @param {Object} row
 */
function formatHelpdeskSubject(cell, rawValue, row) {
  const wrap = document.createElement('div');
  wrap.className = 'helpdesk-subject';

  const icon = document.createElement('div');
  icon.className = 'helpdesk-usericon';
  if (row.avatar) {
    const img = document.createElement('img');
    img.src = row.avatar;
    img.alt = row.customer || '';
    icon.appendChild(img);
  } else {
    const span = document.createElement('span');
    span.setAttribute('data-letters', row.initials || '?');
    icon.appendChild(span);
  }
  wrap.appendChild(icon);

  const body = document.createElement('div');

  const head = document.createElement('div');
  head.className = 'helpdesk-subject-head';
  const no = document.createElement('small');
  no.className = 'helpdesk-no';
  no.textContent = '#' + (row.ticket_no || '');
  head.appendChild(no);
  head.appendChild(document.createTextNode(' ' + (row.customer || '')));
  body.appendChild(head);

  const link = document.createElement('a');
  link.className = 'helpdesk-subject-link one_line';
  link.href = '/helpdesk-detail?id=' + row.id;
  link.title = rawValue || '';
  link.textContent = rawValue || '';
  body.appendChild(link);

  const meta = document.createElement('div');
  meta.className = 'helpdesk-meta';
  meta.appendChild(helpdeskMetaTag('icon-subcategory', row.category_text));
  meta.appendChild(helpdeskMetaTag('icon-calendar', row.created_text));

  const priority = document.createElement('span');
  priority.className = 'helpdesk-priority';
  priority.style.setProperty('--bg-color', row.priority_color || '#666');
  priority.textContent = row.priority_text || '';
  meta.appendChild(priority);

  meta.appendChild(helpdeskMetaTag('icon-customer', row.agent));
  if (row.attachments > 0) {
    meta.appendChild(helpdeskMetaTag('icon-attach', String(row.attachments)));
  }
  if (row.is_overdue) {
    const overdue = document.createElement('span');
    overdue.className = 'helpdesk-overdue icon-clock';
    overdue.textContent = Now.translate('Overdue') + ' ' + (row.due_text || '');
    meta.appendChild(overdue);
  }
  body.appendChild(meta);

  wrap.appendChild(body);
  cell.innerHTML = '';
  cell.appendChild(wrap);
}

/**
 * ป้ายข้อมูลย่อยหนึ่งชิ้นในคอลัมน์หัวเรื่อง
 *
 * @param {string} className
 * @param {string} text
 *
 * @returns {HTMLElement}
 */
function helpdeskMetaTag(className, text) {
  const span = document.createElement('span');
  span.className = className;
  span.textContent = text || '';
  return span;
}

/**
 * คอลัมน์สถานะ พร้อมเวลาที่มีความเคลื่อนไหวล่าสุด
 *
 * @param {HTMLElement} cell
 * @param {string} rawValue
 * @param {Object} row
 */
function formatHelpdeskStatus(cell, rawValue, row) {
  const mark = document.createElement('span');
  mark.className = 'helpdesk-status';
  mark.style.backgroundColor = row.status_color || '#666';
  mark.textContent = row.status_text || '';

  const time = document.createElement('small');
  time.className = 'helpdesk-latest';
  time.textContent = row.updated_text || '';

  cell.innerHTML = '';
  cell.appendChild(mark);
  cell.appendChild(time);
}
