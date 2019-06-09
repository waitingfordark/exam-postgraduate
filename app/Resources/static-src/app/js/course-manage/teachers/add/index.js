class StudentAdd {
  constructor() {
    this.init();
  }

  init() {
    let $form = $('#student-add-form');
    let validator = $form.validate({
      onkeyup: false,
      currentDom:'#student-add-submit',
      rules: {
        queryfield: {
          required: true,
          
        }
      },
      messages: {
        queryfield: {
          remote: Translator.trans('course_manage.student_create.field_required_error_hint')
        }
      }
    });

    $('#student-add-submit').click(function(event) {
      if(validator.form()) {
        $form.submit();
      }
    });
  }
}

new StudentAdd();