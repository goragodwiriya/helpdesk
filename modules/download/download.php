<?php
/**
 * @filesource modules/download/download.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
session_start();

// Validate and sanitize user input
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// check if id and session variable are set
if (empty($id) || !isset($_SESSION[$id])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// retrieve file data from session
$file = $_SESSION[$id];

if (!is_file($file['file'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$f = @fopen($file['file'], 'rb');

if (!$f) {
    header('HTTP/1.0 500 Internal Server Error');
    exit;
}

// Download file
if (empty($file['name'])) {
    header('Content-Disposition: inline');
} else {
    header('Content-Disposition: attachment; filename="'.htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8').'"');
}

if (empty($file['mime'])) {
    header('Content-Type: application/octet-stream');
} else {
    header('Content-Type: '.$file['mime']);
}

header('Content-Description: File Transfer');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: '.filesize($file['file']));

// Output file content
readfile($file['file']);

// Close the file handle
fclose($f);
