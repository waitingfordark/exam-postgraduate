define(function (require, exports, module) {
  var Notify = require('common/bootstrap-notify');
  require("jquery.bootstrap-datetimepicker");

  exports.run = function (options) {
    var $table = $('#work-table');

    $table.on('click', '.ajax_btn', function () {
      var btn_type = $(this).attr('title');
      var removed = $(this).hasClass('removed');
      if (!confirm(Translator.trans('您确认要' + btn_type + '吗？'))) return false;
      $.post($(this).data('url'), function (html) {
        var $tr = $(html);
        if(removed){
          $table.find('#' + $tr.attr('id')).remove();
        }else{
          $table.find('#' + $tr.attr('id')).replaceWith(html);
        }

        Notify.success(Translator.trans(btn_type + '成功！'));
      });
    });

    $("#startDate, #endDate").datetimepicker({
      autoclose: true
    });

  };

});
