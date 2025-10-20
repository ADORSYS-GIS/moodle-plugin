<?php
defined('MOODLE_INTERNAL') || die();

global $PAGE, $USER, $CFG, $OUTPUT;

// Sidebar
$sidepreblockshtml = $OUTPUT->blocks('side-pre');
$sidepostblockshtml = $OUTPUT->blocks('side-post');
$hasblocks = (strpos($sidepreblockshtml, 'data-block=') !== false || strpos($sidepostblockshtml, 'data-block=') !== false);

// User info
$isloggedin = isloggedin() && !isguestuser();
$userpictureurl = $isloggedin ? $OUTPUT->user_picture($USER, ['size'=>100,'link'=>false]) : '';
$username = $isloggedin ? fullname($USER) : '';
$profileurl = $isloggedin ? (new moodle_url('/user/profile.php', ['id'=>$USER->id]))->out(false) : '';
$logouturl = $isloggedin ? (new moodle_url('/login/logout.php', ['sesskey'=>sesskey()]))->out(false) : '';
$loginurl = !$isloggedin ? (new moodle_url('/login/index.php'))->out(false) : '';

// Menu items
$menuitems = [
    ['text'=>get_string('myhome'), 'url'=>new moodle_url('/my/')],
    ['text'=>get_string('courses'), 'url'=>new moodle_url('/course/index.php')],
    ['text'=>get_string('sitehome'), 'url'=>new moodle_url('/')],
];

// Template context
$templatecontext = [
    'sitename' => format_string($SITE->shortname,true,['context'=>context_system::instance()]),
    'config' => $CFG,
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'maincontent' => $OUTPUT->main_content(),   // ✅ MUST include this
    'sidepreblocks' => $sidepreblockshtml,
    'sidepostblocks' => $sidepostblockshtml,
    'hasblocks' => $hasblocks,
    'menuitems' => $menuitems,
    'isloggedin' => $isloggedin,
    'userpictureurl' => $userpictureurl,
    'username' => $username,
    'profileurl' => $profileurl,
    'logouturl' => $logouturl,
    'loginurl' => $loginurl,
];

echo $OUTPUT->render_from_template('theme_adorsys_theme_v2/columns', $templatecontext);
