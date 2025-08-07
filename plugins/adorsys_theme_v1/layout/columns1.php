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

<div id="page" class="min-h-screen">
    <header>
        <?= $OUTPUT->page_heading() ?>
    </header>

    <main id="region-main">
        <?= $OUTPUT->main_content() ?>
    </main>

    <footer>
        <?= $OUTPUT->standard_footer_html() ?>
    </footer>
</div>

<?= $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
