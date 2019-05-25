import notify from 'common/notify';
let $sortableList = $('#sortable-list');

$('#chapter-title-field').on('keypress', function (e) {
  if ((e.keyCode || e.which) === 13) {
    e.preventDefault();
  }
});

$('#course-chapter-btn').on('click', function () {
  let $this = $(this);
  let $form = $('#course-chapter-form');
  let chapterId = $form.data('chapterId');

  let validator = $form.validate({
    rules: {
      title: 'required'
    },
    ajax: true,
    currentDom: $this,
    submitSuccess: function (html) {
      let title = $form.find('#chapter-title-field').val();
      $('.modal').modal('hide');
      $('.js-task-empty').addClass('hidden');
      if (chapterId > 0) {
        $('#chapter-'+chapterId).find('.title').text(title);
      } else {
        $sortableList.trigger('addItem', html);
      }
    },
  });
});