import notify from 'common/notify';

$('.js-python-work-manage').click(function () {
  $.get($(this).data('url'),function (json) {
    if(json.success){
      notify('success',Translator.trans('操作成功！'));
      window.location.reload();
    }else{
      notify('danger',json.message);
    }
  });
});