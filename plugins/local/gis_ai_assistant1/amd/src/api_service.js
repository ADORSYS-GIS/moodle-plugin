define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';
    function send(methodname, args) {
        return Ajax.call([{
            methodname: methodname,
            args: args || {}
        }])[0].catch(function(err){ Notification.exception(err); throw err; });
    }
    return { send: send };
});
