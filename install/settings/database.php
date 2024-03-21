<?php
/* settings/database.php */

return array(
    'mysql' => array(
        'dbdriver' => 'mysql',
        'username' => 'root',
        'password' => '',
        'dbname' => 'helpdesk',
        'prefix' => 'app'
    ),
    'tables' => array(
        'category' => 'category',
        'helpdesk' => 'helpdesk',
        'helpdesk_status' => 'helpdesk_status',
        'language' => 'language',
        'logs' => 'logs',
        'number' => 'number',
        'user' => 'user',
        'user_meta' => 'user_meta'
    )
);
