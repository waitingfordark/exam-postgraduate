$('#approval-form').validate({
  rules: {
    idcard: 'required idcardNumber',
    truename: {
      required: true,
      chinese: true,
      maxlength: 25,
      minlength: 2
    },
    faceImg: 'required isImage limitSize',
    backImg: 'required isImage limitSize'
  },
  messages: {
    faceImg: {
      required: Translator.trans('user.fields.idcard_front_placeholder')
    },
    backImg: {
      required: Translator.trans('user.fields.idcard_back_placeholder')
    }
  }
});

cd.upload({
  el: '.js-upload-input',
}).on('success', (event, file, src) => {
  let $this = $(event.currentTarget);
  let $target = $($this.data('target'));

  $target.addClass('done').css({
    'background-image': `url(${src})`,
  });

  if (!$target.find('.mask').length) {
    let html = '<div class="mask"></div>';
    $target.append(html);
  }
}).on('error', (code) => {
  if (code === 'FILE_SIZE_LIMIT') {
    cd.message({
      type: 'danger',
      message: Translator.trans('uploader.size_2m_limit_hint')
    });
  } else if (code === 'FLIE_TYPE_LIMIT') {
    cd.message({
      type: 'danger',
      message: Translator.trans('uploader.type_denied_limit_hint')
    });
  }
});
