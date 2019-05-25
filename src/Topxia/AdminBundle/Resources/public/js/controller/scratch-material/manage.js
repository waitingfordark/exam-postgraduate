define(function (require, exports, module) {
  var Notify = require('common/bootstrap-notify');
  require('../widget/category-select').run('scratch_material');

  exports.run = function (options) {
    var $table = $('#material-table');

    $table.on('click', '.close-material', function () {
      if (!confirm(Translator.trans('您确认要关闭此素材吗？素材关闭后，仍然还在有效期内的用户将可以继续使用。'))) return false;
      $.post($(this).data('url'), function (html) {
        var $tr = $(html);
        $table.find('#' + $tr.attr('id')).replaceWith(html);
        Notify.success(Translator.trans('素材关闭成功！'));
      });
    });

    $table.on('click', '.publish-material', function () {
      if (!confirm(Translator.trans('您确认要发布此素材吗？'))) return false;
      $.post($(this).data('url'), function (response) {
        if (!response['success'] && response['message']) {
          Notify.danger(response['message']);
        } else {
          var $tr = $(response);
          $table.find('#' + $tr.attr('id')).replaceWith($tr);
          Notify.success(Translator.trans('素材发布成功！'));
        }
      }).error(function (e) {
        var res = e.responseJSON.error.message || '未知错误';
        Notify.danger(res);
      });
    });

    $table.on('click', '.delete-material', function () {
      var $tr = $(this).parents('tr');

      if (!confirm(Translator.trans('您确认要删除此素材吗？'))) return false;
      $.post($(this).data('url'), function (response) {
        if (!response['success'] && response['message']) {
          Notify.danger(response['message']);
        } else {
          Notify.success(Translator.trans('素材删除成功！'));
          $tr.remove();
        }
      }).error(function (e) {
        var res = e.responseJSON.error.message || '未知错误';
        Notify.danger(res);
      });
    });
  };

});
