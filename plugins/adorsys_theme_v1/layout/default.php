<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Default layout for Adorsys Theme v1.
 *
 * @package    theme_adorsys_theme_v1
 */

// Output the page.
echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $PAGE->title; ?></title>
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body <?php echo $OUTPUT->body_attributes(); ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div id="page" class="min-h-screen bg-gray-100 text-gray-900">
    <header id="page-header" class="bg-white shadow">
        <div class="container mx-auto p-4">
            <?php echo $OUTPUT->page_heading(); ?>
        </div>
    </header>

    <div id="page-content" class="container mx-auto flex flex-col md:flex-row p-4">
        <?php if (!empty($PAGE->blocks->region_has_content('side-pre', $OUTPUT))) : ?>
            <aside id="block-region-side-pre" class="w-full md:w-1/4 p-2">
                <?php echo $OUTPUT->blocks('side-pre'); ?>
            </aside>
        <?php endif; ?>

        <main id="region-main" class="flex-1 p-2">
            <?php echo $OUTPUT->main_content(); ?>
        </main>

        <?php if (!empty($PAGE->blocks->region_has_content('side-post', $OUTPUT))) : ?>
            <aside id="block-region-side-post" class="w-full md:w-1/4 p-2">
                <?php echo $OUTPUT->blocks('side-post'); ?>
            </aside>
        <?php endif; ?>
    </div>

    <footer id="page-footer" class="bg-white mt-8 border-t">
        <div class="container mx-auto p-4 text-sm text-gray-600">
            <?php echo $OUTPUT->standard_footer_html(); ?>
        </div>
    </footer>
</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>
