<?php
// Saves the light / dark mode chosen with the switch button as the user's ResourceSpace
// appearance preference ("user_pref_appearance": light, dark or device - the same setting as on the
// user preferences page). Called by the switch button (POST, CSRF token checked by authenticate.php).
// Since 1.17.
include '../../../include/db.php';
include '../../../include/authenticate.php';
require_once dirname(__FILE__, 2) . '/include/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$reply = function ($code, array $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $reply(405, array('error' => 'POST only'));
}
if (!customcss_settings()['switch']) {
    $reply(403, array('error' => 'The light / dark mode switch is turned off.'));
}
$user = (int) ($userref ?? 0);
if ($user <= 0 || (isset($anonymous_login) && ($username ?? '') === $anonymous_login)) {
    $reply(403, array('error' => 'Only logged-in users can save the mode.')); // the cookie still works
}
$mode = (string) ($_POST['mode'] ?? '');
$map  = array('light' => 'light', 'dark' => 'dark', 'auto' => 'device');
if (!isset($map[$mode])) {
    $reply(400, array('error' => 'Unknown mode.'));
}
if (!function_exists('set_config_option') || !set_config_option($user, 'user_pref_appearance', $map[$mode])) {
    $reply(500, array('error' => 'The appearance preference could not be saved.'));
}
$reply(200, array('ok' => true, 'appearance' => $map[$mode]));
