import sortList from 'common/sortable';

export default class Manage {
  constructor(element) {
    this.$element = $(element);
    
    this._event();
  }

  _event() {
    let self = this;

    this.$element.on('addItem', function(e, elm) {
      self.addItem(elm);
     
    });

    $('body').on('click', '[data-position]', function(e) {
      let $this = $(this);

      self.position = $this.data('position');
      self.type = $this.data('type');
    });
  
    this._deleteChapter();
    this._publish();
    // this._createTask();
    
  }

  addItem(elm) {
    let $elm = $(elm);
    let $exsit = $('#' + $elm.attr('id'));

    //编辑时，替换元素
    if ($exsit.length > 0) {
      $exsit.replaceWith($elm);
      this.afterAddItem($elm);
      return;
    }
   
    switch (this.type) {
    case 'task':
      this.$element.find('#chapter-' + this.position + ' .js-lesson-box').append($elm);
      break;
    case 'lesson':
    {
      let $unit = this.$element.find('#chapter-' + this.position);
      let $lesson = $unit.nextUntil('.js-task-manage-unit,.js-task-manage-chapter').last();
      if (0 == $lesson.length) {
        $unit.after($elm);
      } else {
        $lesson.after($elm);
      }
      break;
    }
    default:
      this.$element.append($elm);
    }
    $('[data-toggle="tooltip"]').tooltip();

    this.handleEmptyShow();
    this._flushTaskNumber();
    this.clearPosition();
    this.afterAddItem($elm);
  }

  clearPosition() {
    this.position = '';
    this.type = '';
  }

  



  

  handleEmptyShow() {
    if (0 === $('#sortable-list').find('li').length) {
      $('.js-task-empty').removeClass('hidden');
    } else {
      $('.js-task-empty').addClass('hidden');
    }
  }


  _publish() {
    const info = {
      class: '.js-publish-item, .js-delete, .js-lesson-unpublish-status',
      oppositeClas: '.js-unpublish-item',
      flag: false
    };
    this.$element.on('click', '.js-unpublish-item', (event) => {
      const $target = $(event.target);
      info.success = Translator.trans('course.manage.task_unpublish_success_hint'),
      info.danger = Translator.trans('course.manage.task_unpublish_fail_hint') + ':',
      this.toggleOptional($target, self, info);
    });

    this.$element.on('click', '.js-publish-item', (event) => {
      const $target = $(event.target);
      info.success = Translator.trans('course.manage.task_publish_success_hint'),
      info.danger = Translator.trans('course.manage.task_publish_fail_hint') + ':',
      this.toggleOptional($target, self, info);
    });
  }

  _deleteChapter() {
    //删除章节课时
    let self = this;
    this.$element.on('click', '.js-delete', function(evt) {
      let $this = $(this);
      let $parent = $this.closest('.task-manage-item');
      let text = self._getDeleteText($this);

      cd.confirm({
        title: Translator.trans('site.delete'),
        content: text,
        okText: Translator.trans('site.confirm'),
        cancelText: Translator.trans('site.cancel')
      }).on('ok', () => {
        if ('task' == $this.data('type') && $parent.siblings().length == 0) {
          $parent.closest('.js-task-manage-lesson').remove();
        }
        $parent.remove();

        self.handleEmptyShow();
        $.post($this.data('url'), function(data) {
          location.reload();
        });
      });
    });
  }

  _getDeleteText($element) {
    // 获得删除章节课时时，提示文案
    if ('task' == $element.data('type')) {
      return Translator.trans('course.manage.task_delete_hint', { taskName: $element.data('name') });
    }
    return Translator.trans('course.manage.chapter_delete_hint', { name: $element.data('name') });
  }

  _optional() {
    let self = this;
    const info = {
      class: '.js-set-optional',
      oppositeClas: '.js-unset-optional,.js-lesson-option-tag',
      success: Translator.trans('site.save_success_hint'),
      danger: Translator.trans('site.save_error_hint') + ':',
      flag: true
    };
    this.$element.on('click', '.js-set-optional', (event) => {
      const $target = $(event.target);
      self.toggleOptional($target, self, info);
    });

    this.$element.on('click', '.js-unset-optional', (event) => {
      const $target = $(event.target);
      self.toggleOptional($target, self, info);
    });
  }

  toggleOptional($target, self, info) {
    const $parentLi = $target.closest('.task-manage-item');
    const $dom = $parentLi.find(info.class);
    const $oppositeDom = $parentLi.find(info.oppositeClas);
    $.post($target.data('url'), (data) => {
      $dom.toggleClass('hidden');
      $oppositeDom.toggleClass('hidden');
      location.reload();
      // cd.message({ type: 'success', message: info.success });
    }).fail(function(data) {
      alert('操作失败');
    });
  }


  afterAddItem($elm) {
    console.log('afterAddItem');
  }
}