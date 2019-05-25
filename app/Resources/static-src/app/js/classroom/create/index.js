import 'app/js/classroom-manage/classroom-create';

let $form = $('#classroom-create-form');

let validator = $form.validate({
  rules: {
    title: {
      required: true,
      minlength: 2,
      maxlength: 30
    }
  }
});

$form.on('click', '#classroom-create-btn', (event) => {
  if (validator && validator.form()) {
    $form.submit();
  }
});
