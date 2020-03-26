import notify from 'common/notify';
import BatchSelect from '../../common/widget/batch-select';
import DeleteAction from '../../common/widget/delete-action';

class TestpaperManage
{
  constructor($container) {

    this.$container = $container;
    this._initEvent();
    this._init();
  }

  _initEvent() {
    this.$container.on('click','.open-testpaper,.close-testpaper',event=>this.testpaperAction(event));

  }

  _init() {

  }

  testpaperAction(event) {
    let $target = $(event.currentTarget);
    let $tr = $target.closest('tr');

    if (!confirm('确认？')) {
      return ;
    }

    $.post($target.data('url'), function(html){
      $tr.replaceWith(html);
    }).error(function(){
      
    });
  }
  
}

let $container = $('#quiz-table-container');
new TestpaperManage($container);
new BatchSelect($container);
new DeleteAction($container);