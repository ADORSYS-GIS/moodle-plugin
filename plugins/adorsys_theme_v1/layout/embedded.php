<?php
defined('MOODLE_INTERNAL') || die();

echo $OUTPUT->doctype();
?>
<html <?= $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?= $PAGE->title ?></title>
    <?= $OUTPUT->standard_head_html(); ?>
</head>
<body <?= $OUTPUT->body_attributes(); ?>>
<?= $OUTPUT->main_content(); ?>
</body>
</html>
