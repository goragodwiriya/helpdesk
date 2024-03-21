<?php
/* config.php */
return array(
    'version' => '1.0.0',
    'web_title' => 'SUPPORT CENTER',
    'web_description' => 'ระบบช่วยเหลือลูกค้าออนไลน์',
    'timezone' => 'Asia/Bangkok',
    'member_status' => array(
        0 => 'สมาชิก',
        1 => 'ผู้ดูแลระบบ',
        2 => 'หัวหน้าแผนก',
        3 => 'Agent'
    ),
    'color_status' => array(
        0 => '#259B24',
        1 => '#FF0000',
        2 => '#0E0EDA',
        3 => '#827717'
    ),
    'default_icon' => 'icon-support',
    'helpdesk_w' => 600,
    'helpdesk_prefix' => '%Y%M',
    'helpdesk_no' => '%03d'
);
