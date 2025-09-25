/*
 * Gruntfile for building Moodle AMD modules in this plugin only.
 *
 * It minifies all files in amd/src/*.js into amd/build/*.min.js
 * while preserving AMD define() wrappers and license headers.
 *
 * Usage:
 *   1) npm install
 *   2) npx grunt amd   (or: npx grunt)
 *
 * After building, purge Moodle caches and reload the page.
 */

module.exports = function(grunt) {
    'use strict';

    // Load tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');

    // Metadata banner.
    const BANNER = '/*! Built <%= grunt.template.today("yyyy-mm-dd HH:MM:ss") %> - ' +
                   'Moodle plugin local_gis_ai_assistant - AMD build */\n';

    grunt.initConfig({
        pkg: grunt.file.exists('package.json') ? grunt.file.readJSON('package.json') : {},

        uglify: {
            options: {
                banner: BANNER,
                sourceMap: true,
                sourceMapIncludeSources: true,
                // Keep AMD define() intact; we do not need aggressive mangling.
                mangle: false,
                compress: {
                    passes: 2,
                    pure_getters: true,
                    unsafe: false
                },
                output: {
                    // Keep license/comments if present.
                    comments: /!|@preserve|@license|@cc_on/i
                }
            },
            build: {
                files: [{
                    expand: true,
                    cwd: 'amd/src',
                    src: ['*.js'],
                    dest: 'amd/build',
                    ext: '.min.js'
                }]
            }
        }
    });

    // Aliases
    grunt.registerTask('amd', ['uglify:build']);
    grunt.registerTask('default', ['amd']);
};
