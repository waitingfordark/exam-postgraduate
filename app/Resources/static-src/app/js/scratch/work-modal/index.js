import layer from 'layui-layer';

$('#save-btn').on('click', function () {
  var title = $('#title').val();
  var image = $('#scratch-cover').attr('src');
  if(title == '' || image == ''){
    layer.msg('请填写作品名和封面');
    return;
  }
  $.ajax({
    url: '/scratch/work/' + window.project_id + '/Record',
    type: 'POST',
    data: 'title=' + title + '&image=' + image + '&publish=' + window.published,
    success: function (json, status) {

      if (json.status) {
        layer.closeAll();
        layer.msg('作品保存成功');
      } else {
        layer.msg('作品保存失败');
      }

      if (window.published) {
        layer.msg('作品发布成功');
        window.published = false;
      }

    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      layer.msg('作品保存失败');
      if (window.published) {
        layer.msg('作品发布失败');
        window.published = false;
      }
    }
  });

});