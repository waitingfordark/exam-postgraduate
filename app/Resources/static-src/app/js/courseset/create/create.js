export default class Create {
  constructor($element) {
    this.$element = $element;
    this.init();
  }

  init() {
    this.validator = this.$element.validate({
      rules: {
        title: {
          maxlength: 60,
          required: true,
          trim: true,
          course_title: true,
        }
      },
      messages: {
        title: {
          required: Translator.trans('course_set.title_required_error_hint'),
          trim: Translator.trans('course_set.title_required_error_hint'),
        }
      }
    });

    $('input[name="type"]').val('normal');
    let $title = $('#course_title');

  }
}