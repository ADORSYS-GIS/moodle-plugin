define([], function() {
    'use strict';
    function $(sel, root) { return (root || document).querySelector(sel); }
    function $all(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }
    return { $, $all };
});
