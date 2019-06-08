define(function (require, exports, module) {
  var Notify = require('common/bootstrap-notify');
  require('../widget/category-select').run('course');
  var CourseSetClone = require('../course-set/clone');

  exports.run = function (options) {

    var csl = new CourseSetClone();

    var $table = $('#course-table');

    $table.on('click', '.close-course', function () {
      var user_name = $(this).data('user');
      if (!confirm(Translator.trans('您确认要关闭此课程吗？'))) return false;
      $.post($(this).data('url'), function (html) {
        var $tr = $(html);
        $table.find('#' + $tr.attr('id')).replaceWith(html);
        Notify.success(Translator.trans('课程关闭成功！'));
      });
    });

    $table.on('click', '.publish-course', function() {
      var studentNum = $(this).closest('tr').next().val();
      if (!confirm(Translator.trans('您确认要发布此课程吗？'))) return false;
      $.post($(this).data('url'), function(response) {
        if (!response['success'] && response['message']) {
          Notify.danger(response['message']);
        } else {
          var $tr = $(response);
          $table.find('#' + $tr.attr('id')).replaceWith($tr);
          Notify.success(Translator.trans('课程发布成功！'));
        }
      }).error(function(e) {
        var res = e.responseJSON.error.message || '未知错误';
        Notify.danger(res);
      });
    });

    $table.on('click', '.delete-course', function() {
      var chapter_name = $(this).data('chapter');
      var part_name = $(this).data('part');
      var user_name = $(this).data('user');
      var $this = $(this);
      if (!confirm(Translator.trans('真的要删除该课程吗？')))
        return;
      var $tr = $this.parents('tr');
      $.post($this.data('url'), function(data) {
        if (data.code > 0) {
          Notify.danger(data.message);
        } else if (data.code == 0) {
          $tr.remove();
          Notify.success(data.message);
        } else {
          $('#modal').modal('show').html(data);
        }
      });
    });
  };

});
