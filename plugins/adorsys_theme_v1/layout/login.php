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
<?= $OUTPUT->standard_top_of_body_html(); ?>

<main id="region-main" class="login-page min-h-screen flex items-center justify-center bg-gray-100">
    <div class="login-box p-6 bg-white shadow">
        <?= $OUTPUT->main_content(); ?>
    </div>
</main>

<?= $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>
