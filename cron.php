<?php

/**
 * cron.php
 * หน้าเว็บเรียกให้ทำงานตามเวลา (Cron Job)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
// load Kotchasan
include 'load.php';
// Initial Kotchasan Framework
$app = Kotchasan::createWebApplication('Gcms\Config');
$app->defaultController = 'Index\Cron\Controller';
$app->run();
