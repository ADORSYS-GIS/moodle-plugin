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

<main id="region-main" class="secure-page min-h-screen p-4">
    <?= $OUTPUT->main_content(); ?>
</main>

<?= $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>
