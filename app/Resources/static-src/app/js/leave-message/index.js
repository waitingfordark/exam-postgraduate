import notify from 'common/notify';

let $form = $('#leave-message-form');
let validator = $form.validate({
  rules: {
    name: {
      maxlength: 32,
      required: true,
    },
    email: {
      required: false,
      email: true,
    },
    phone: {
      required: true,
      phone: true
    },
  },
  messages: {

  }
});
$('#leave-message-commit').click((event) => {
  if (validator && validator.form()) {
    notify('success', '提交成功');
    $(event.currentTarget).button('loading');
    $form.submit();
  }
});