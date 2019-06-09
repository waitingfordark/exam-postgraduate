import BatchSelect from 'app/common/widget/batch-select';
new BatchSelect($('#student-table-container'));
class Students {
  constructor() {
    this.initDeleteActions();
  }

  initDeleteActions() {
    $('body').on('click', '.js-remove-student', function(evt) {
      if (!confirm(Translator.trans('course.manage.student_delete_hint'))) {
        return;
      }
      $.post($(evt.target).data('url'), function (data) {
        if (data.success) {
          cd.message({ type: 'success', message: Translator.trans('site.delete_success_hint') });
          location.reload();
        } else {
          cd.message({ type: 'danger', message: Translator.trans('site.delete_fail_hint') + ':' + data.message });
        }
      });
    });
  }

}

new Students();