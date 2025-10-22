define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    function renderRows(root, rows) {
        if (!Array.isArray(rows)) { rows = []; }
        var html = rows.map(function(r){
            var metric = String(r.metric || '');
            var value = (r.value === null || r.value === undefined) ? '' : String(r.value);
            return '<div class="row"><span class="metric">' + metric + '</span>: <span class="value">' + value + '</span></div>';
        }).join('');
        root.innerHTML = '<div class="aiprovider-gis-analytics">' + html + '</div>';
    }

    function init(rootSelector, params) {
        var root = document.querySelector(rootSelector || '.aiprovider-gis-analytics');
        if (!root) { return; }
        var args = params || {};
        Ajax.call([{ methodname: 'aiprovider_gis_ai_get_analytics', args: args }])[0]
            .then(function(resp){ renderRows(root, resp && resp.rows); })
            .catch(function(err){ Notification.exception(err); });
    }

    return { init: init };
});
