<?php
defined('MOODLE_INTERNAL') || die();

echo $OUTPUT->doctype();
?>
<html <?= $OUTPUT->htmlattributes() ?>>
<head>
    <title><?= $PAGE->title ?></title>
    <?= $OUTPUT->standard_head_html() ?>
</head>
<body <?= $OUTPUT->body_attributes() ?>>
<?= $OUTPUT->standard_top_of_body_html() ?>

<div id="page" class="min-h-screen flex">
    <aside id="region-side-pre" class="w-1/4 p-4 bg-gray-200">
        <?= $OUTPUT->blocks('side-pre') ?>
    </aside>

    <main id="region-main" class="flex-1 p-4">
        <?= $OUTPUT->main_content() ?>
    </main>
</div>

<footer class="p-4">
    <?= $OUTPUT->standard_footer_html() ?>
</footer>

<?= $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
