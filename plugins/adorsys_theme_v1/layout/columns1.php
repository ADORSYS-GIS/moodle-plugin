<?php
defined('MOODLE_INTERNAL') || die();

echo $OUTPUT->doctype();
?>
<html <?= $OUTPUT->htmlattributes() ?>>
<head>
    <title><?= $PAGE->title ?></title>
    <?= $OUTPUT->standard_head_html() ?>
</head>
<body <?= $OUTPUT->body_attributes() ?> class="bg-gray-100 text-gray-900 font-sans">
<?= $OUTPUT->standard_top_of_body_html() ?>

<div id="page" class="min-h-screen flex flex-col">
    <header class="bg-white shadow p-4">
        <?= $OUTPUT->page_heading() ?>
    </header>

    <div class="flex flex-col md:flex-row container mx-auto p-4 gap-4">
        <?php if (!empty($PAGE->blocks->region_has_content('side-pre', $OUTPUT))) : ?>
            <aside id="region-side-pre" class="w-full md:w-1/4 space-y-4">
                <div class="bg-white rounded shadow p-4">
                    <?= $OUTPUT->blocks('side-pre') ?>
                </div>
            </aside>
        <?php endif; ?>

        <main id="region-main" class="flex-1 bg-white rounded shadow p-4">
            <?= $OUTPUT->main_content() ?>
        </main>
    </div>

    <footer class="bg-white border-t mt-8 p-4 text-sm text-gray-600">
        <?= $OUTPUT->standard_footer_html() ?>
    </footer>
</div>

<?= $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
