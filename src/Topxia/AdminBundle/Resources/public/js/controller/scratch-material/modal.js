define(function (require, exports, module) {
  var WebUploader = require('edusoho.webuploader'); // ForCustomerOnly
  var Validator = require('bootstrap.validator');
  var Notify = require('common/bootstrap-notify');
  require('common/validator-rules').inject(Validator);

  exports.run = function () {
    var $form = $('#material-form');
    var $modal = $form.parents('.modal');
    var uploader = new WebUploader({
      element: '#file-uploader'
    });

    uploader.on('uploadSuccess', function (file, response) {
      var url = $("#file-uploader").data("gotoUrl");
      var oldFileUri = $("#file-uploader").data('uri');
      $.post(url, {response: response, oldFileUri: oldFileUri}, function (data) {
        $("#file-src").html('<img class="img-responsive" src="' + data.url + '">');
        $form.find('[name=fileUri]').val(data.path);
        Notify.success(Translator.trans('上传分类图标成功！'));
      }).error(function () {
        Notify.danger(Translator.trans('上传分类图标失败！'));
      });
    });

    var validator = new Validator({
      element: $form,
      autoSubmit: false,
      onFormValidated: function (error, results, $form) {
        if (error) {
          return;
        }

        $('#material-submit-btn').button('loading').addClass('disabled');

        $.post($form.attr('action'), $form.serialize()).done(function (html) {
          $modal.modal('hide');
          Notify.success(Translator.trans('保存分类成功！'));
          // $table.find('tbody').replaceWith(html);
          window.location.reload();
        }).fail(function () {
          Notify.danger(Translator.trans('添加分类失败，请重试！'));
        });

      }
    });

    validator.addItem({
      element: '#title',
      required: true,
      rule: 'chinese_alphanumeric byte_maxlength{max:20}'
    });

    validator.addItem({
      element: '#categoryId',
      required: true,
      errormessageRequired: Translator.trans('请先选择分类')
    });

    validator.addItem({
      element: '#price',
      required: true,
      rule: 'integer min{min: 0} max{max: 100000}',
    });

    validator.addItem({
      element: '[name="fileUri"]',
      required: true,
      errormessageRequired: Translator.trans('请先上传文件')
    });
  };

});
