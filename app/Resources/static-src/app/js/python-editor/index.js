import { Browser, isLogin } from 'common/utils';
import 'jquery.cookie';
import _ from 'lodash';

let $loginModal = $('#login-modal');
$(function(){
  initLoadCode();
  window.editor = window.CodeMirror.fromTextArea(document.getElementById('yourcode'), {
    mode: 'python',    //实现python代码高亮
    lineNumbers: true,	//显示行号
    theme: 'dracula',	//设置主题
    lineWrapping: true,	//代码折叠
    foldGutter: true,
    gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
    matchBrackets: true,	//括号匹配
  });

  initSize();

  $('.python-run-result>.clear-btn').click(function () {
    document.getElementById('output').innerHTML = '';
    document.getElementById('mycanvas').innerHTML = '';
  });

  $('body').click(function (e) {
    if(e.target == $('.file-group')[0] || e.target == $('img.file-icon')[0]){
      return false;
    }
    $('.file-list').hide();
  });

  $('.file-group').click(function () {
    $('.file-list').toggle();

  });

  $('.file-open').click(function () {
    $('#files').click();
  });

  $('.file-new').click(function () {
    if (!confirm('当前作品未保存,是否要保存?')) {
      window.location.href = $(this).data('url');
    }
    $('.save-work').click();
  });
  
  $('#files').change(function () {
    //读取文件的File对象
    var selectedFile = document.getElementById('files').files[0];
    if(selectedFile !== undefined){
      var name = selectedFile.name;//读取选中文件的文件名
      var size = selectedFile.size;//读取选中文件的大小
      console.log('文件名:'+name+'大小:'+size);

      var reader = new FileReader();//这是核心,读取操作就是由它完成.
      reader.readAsText(selectedFile);//读取文件的内容,也可以读取文件的URL
      reader.onload = function () {
        //当读取完成后回调这个函数,然后此时文件的内容存储到了result中,直接操作即可
        window.editor.setValue(this.result);
        document.getElementById('files').value = '';
      };
    }

  });
  
  
  $('.file-save').click(function () {
    var file = new File([window.editor.getValue()], 'my_python_work.py', { type: 'text/plain;charset=utf-8' });
    window.saveAs(file);
  });

  $('.save-work').click(function () {
    if(!isLogin()){
      isNotLogin();
      return;
    }
    window.published = false;
    loadSaveWorks($(this).data('url'));

  });

  $('.publish-work').click(function () {
    if(!isLogin()){
      isNotLogin();
      return;
    }
    window.published = true;
    loadSaveWorks($(this).data('url'));

  });

});
function initSize() {
  if (!window.Sk.TurtleGraphics) {
    window.Sk.TurtleGraphics = {};
  }
  if(document.documentElement.clientWidth<991){
    window.editor.setSize(document.documentElement.clientWidth + 'px', '300px');
    $('#mycanvas').css('height','300px');
    $('#output').css('height','200px');
    window.Sk.TurtleGraphics.height = 300;
    window.Sk.TurtleGraphics.width = document.documentElement.clientWidth;
  }else{
    window.editor.setSize(document.documentElement.clientWidth/2 + 'px', document.documentElement.clientHeight - 42 + 'px');
    $('#mycanvas').css('height',(document.documentElement.clientHeight - 42)/2 + 'px');
    $('#output').css('height',(document.documentElement.clientHeight - 42)/2 + 'px');
    window.Sk.TurtleGraphics.height = (document.documentElement.clientHeight - 42)/2;
    window.Sk.TurtleGraphics.width = document.documentElement.clientWidth/2;
  }
}

function initLoadCode() {
  let tmp_code = $.cookie('tmp_python_code');
  if(!_.isEmpty(tmp_code)){
    $.removeCookie('tmp_python_code');
    $('#yourcode').val(tmp_code);
  }else {
    $('#yourcode').val(decodeURIComponent($('#yourcode').val()));
  }
}
function loadSaveWorks(url) {
  $.ajax({
    url: url,
    type: 'GET',
    success: function (content, status) {
      $loginModal.modal('show');
      $loginModal.html(content);
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert('啊哦，失败了，再试一下吧');
    }
  });
}
function isNotLogin(){
  $.cookie('tmp_python_code', window.editor.getValue());
  $('.modal').modal('hide');
  $loginModal.modal('show');
  $.get($loginModal.data('url'), function (html) {
    $loginModal.html(html);
  });
}
// output functions are configurable.  This one just appends some text
// to a pre element.
window.outf = function (text) {
  var mypre = document.getElementById('output');
  mypre.innerHTML = mypre.innerHTML + text;
};
window.builtinRead = function (x) {
  if (window.Sk.builtinFiles === undefined || window.Sk.builtinFiles['files'][x] === undefined)
    throw 'File not found: \'' + x + '\'';
  return window.Sk.builtinFiles['files'][x];
};
// Here's everything you need to run a python program in skulpt
// grab the code from your textarea
// get a reference to your pre element for output
// configure the output function
// call Sk.importMainWithBody()
window.runit = function () {
  initSize();
  var prog = window.editor.getValue();
  var mypre = document.getElementById('output');
  mypre.innerHTML = '';
  window.Sk.pre = 'output';
  window.Sk.configure({output:window.outf, read:window.builtinRead});
  (window.Sk.TurtleGraphics || (window.Sk.TurtleGraphics = {})).target = 'mycanvas';
  var myPromise = window.Sk.misceval.asyncToPromise(function() {
    return window.Sk.importMainWithBody('<stdin>', false, prog, true);
  });
  myPromise.then(function(mod) {
    console.log('success');
  },
  function(err) {
    alert(err.toString());
    console.log(err.toString());
  });
};