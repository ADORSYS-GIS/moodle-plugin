<?php
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'adorsys_theme_v1';
$THEME->parents = ['boost'];
$THEME->sheets = ['all'];
$THEME->javascripts = ['theme'];
$THEME->editor_sheets = [];
$THEME->enable_dock = false;
$THEME->rendererfactory = 'theme_overridden_renderer_factory';