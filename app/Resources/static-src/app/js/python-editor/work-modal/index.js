import layer from 'layui-layer';
import 'jquery-base64';
import 'jquery-qrcode';
$('#previewcode').val(window.editor.getValue());
window.previeweditor = window.CodeMirror.fromTextArea(document.getElementById('previewcode'), {
  mode: 'python',    //实现python代码高亮
  lineNumbers: true,	//显示行号
  theme: 'dracula',	//设置主题
  lineWrapping: true,	//代码折叠
  foldGutter: true,
  gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
  matchBrackets: true,	//括号匹配
});
window.previeweditor.setOption('readOnly', true);
$('#previewcanvas').css('height','163px');
if (!window.Sk.TurtleGraphics) {
  window.Sk.TurtleGraphics = {};
}
window.Sk.TurtleGraphics.height = 163;
window.Sk.TurtleGraphics.width = $('#previewcanvas').width();
window.Sk.pre = 'output';
window.Sk.configure({output:null, read:window.builtinRead});
(window.Sk.TurtleGraphics || (window.Sk.TurtleGraphics = {})).target = 'previewcanvas';
var myPromise = window.Sk.misceval.asyncToPromise(function() {
  return window.Sk.importMainWithBody('<stdin>', false, window.editor.getValue(), true);
});

layer.link('../../../../libs/layui-layer.css');
if (window.published === true) {
  $('#save-btn').html('确认发布');
}
$('#save-btn').on('click', function () {
  saveWork('save');
});

function saveWork(type) {
  var title = $('#title').val();
  var code = window.editor.getValue();
  code = encodeURIComponent(code);
  if (title == '') {
    layer.msg('请填写作品名');
    return;
  }
  $('.modal').modal('hide');
  layer.msg('保存中...');
  $.ajax({
    url: $('#work-form').attr('action'),
    type: 'POST',
    data: 'title=' + title + '&code=' + $.base64.encode(code) + '&publish=' + window.published,
    success: function (json, status) {

      if (json.success) {
        layer.closeAll();
        layer.msg('作品保存成功');
      } else {
        layer.msg('作品保存失败');
      }

      if (window.published) {
        layer.msg('作品发布成功');
        window.published = false;
      }

      if (type == 'share') {
        showQrcode(json.url);
      }
      if (type == 'save') {
        setTimeout('window.location.href = \"' + json.url + '\"', 2000);
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
}

function showQrcode(uri) {
  layer.open({
    type: 1,
    title: ['扫码分享', 'text-align: center;font-weight:bold;'],
    shadeClose: false, //点击遮罩关闭
    content: '<div id="qrcode" class="code" style="width: 170px; height: 170px; padding: 10px;"></div>',
    end: function(){
      window.location.href = uri;
    }
  });
  $('#qrcode').qrcode({render: 'canvas', width: 150, height: 150, correctLevel: 0, text: GetUrlPath() + uri});
}

function GetUrlPath() {
  var url = window.location.protocol + '//' + window.location.host;
  return url;
}

$('#share-btn').on('click', function () {
  if (window.published === false) {
    if (!confirm('分享给他人需要先发布作品,确定要发布吗?')) {
      return;
    }
    window.published = true;
  }
  saveWork('share');
});