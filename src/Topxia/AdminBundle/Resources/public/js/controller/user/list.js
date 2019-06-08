define(function(require, exports, module) {

    var Notify = require('common/bootstrap-notify');
    require("jquery.bootstrap-datetimepicker");
    var validator = require('bootstrap.validator');
    exports.run = function() {
        var $datePicker = $('#datePicker');
        var $table = $('#user-table');

        $table.on('click', '.lock-user, .unlock-user', function() {
            var $trigger = $(this);

            if (!confirm(Translator.trans('真的要%title%吗？',{title:$trigger.attr('title')}))) {
                return;
            }

            $.post($(this).data('url'), function(html) {
                Notify.success(Translator.trans('%title%成功！',{title:$trigger.attr('title')}));
                var $tr = $(html);
                $('#' + $tr.attr('id')).replaceWith($tr);
            }).error(function() {
                Notify.danger(Translator.trans('%title%失败',{title:$trigger.attr('title')}));
            });
        });

        var $userSearchForm = $('#user-search-form');

        $('#user-export').on('click', function() {
            var self = $(this);
            var data = $userSearchForm.serialize();
            self.attr('data-url', self.attr('data-url') + "?" + data);
        });
    };

});