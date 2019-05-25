import swfobject from 'es-swfobject';
import layer from 'layui-layer';
import qrcode from 'jquery-qrcode';

var Scratch = Scratch || {};
Scratch.INIT_DATA = Scratch.INIT_DATA || {};

window.SWFready = $.Deferred();

var flashvars = {
  extensionDevMode: 'true',
  autostart: 'false',
  cloudToken: '00000000-0000-0000-0000-000000000000',
  challengeMode: 'false',
  showOnly: 'false',//显示代码
  user_id: window.user_id,
  user_token: window.user_token,
  user_class_id: window.user_class_id,
  project: window.project_id,
  cdnToken: 'e71d264c6d10cd3e8bf248a7f0ccfbd4',
  // project_title: 'Untitled',
  // project_isPrivate: 'true',
  // project_isNew: 'true',
  urlOverrides: {
    sitePrefix: window.location.protocol + '//' + window.location.host + '/',
    siteCdnPrefix: window.location.protocol + '//' + window.location.host + '/',
    assetPrefix: window.location.protocol + '//' + window.location.host + '/',
    assetCdnPrefix: window.location.protocol + '//' + window.location.host + '/',
    projectPrefix: window.location.protocol + '//' + window.location.host + '/',
    projectCdnPrefix: window.location.protocol + '//' + window.location.host + '/',
    internalAPI: 'internalapi/',
    OSS: '',
    defaultProject: '/assets/libs/scratch/project.sb2',
    siteAPI: '/scratch/upload?projectId=' + window.project_id + '&',//api地址
    staticFiles: 'static/',
    mp4: '',
  },
  inIE: (navigator.userAgent.indexOf('MSIE') > -1)
};

$.each(flashvars, function (prop, val) {
  if ($.isPlainObject(val)) {
    flashvars[prop] = encodeURIComponent(JSON.stringify(val));
  }
});


var params = {
  allowscriptaccess: 'always',
  allowfullscreen: 'true',
  wmode: 'direct',
  menu: 'false',
};


for (var i in flashvars) {
  if (typeof params.flashvars !== 'undefined') {
    params.flashvars += '&' + i + '=' + flashvars[i];
  } else {
    params.flashvars = i + '=' + flashvars[i];
  }
}

var swfFile = (swfobject.hasFlashPlayerVersion('11.6.0') ? '/assets/libs/scratch/Scratch.swf' : '/assets/libs/scratch/ScratchFor10.2.swf');
var swfAtt = {
  data: swfFile,
  width: window.innerWidth,
  height: window.innerHeight,
// style:"visibility: visible;"
};

swfobject.addDomLoadEvent(function () {
  var swf = swfobject.createSWF(swfAtt, params, 'scratch');

});

window.uploadImageNum = 0;
window.generateSaveMsg = function () {
  $.ajax({
    url: '/scratch/work/' + window.project_id + '/Record?number=' + window.uploadImageNum,
    type: 'GET',
    success: function (content, status) {
      $('#dialog').css('display', 'none');
      layer.open({
        type: 1,
        title: false,
        closeBtn: 0,
        area: ['600px', '352px'],
        shadeClose: true, //点击遮罩关闭
        content: content
      });
      window.uploadImageNum ++;
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert('啊哦，失败了，再试一下吧');
    }
  });
};

/**
 *分成成功后，生成二维码的函数
 */
window.generateShareMsg = function () {
  $.ajax({
    url: '/scratch/share/' + window.project_id,
    type: 'POST',
    data: 'type=share&projectId=' + window.project_id,
    success: function (json, status) {//如果调用php成功

      window.fileName = json.fileName;
      //填充项目信息
      var userName = json.userName;
      var projectDes = json.projectDes;
      var usageDes = json.usageDes;
      var success = json.status;
      if (!success) {
        userName = '';
        projectDes = '';
        usageDes = '';
      }

      var content = '<div class="shareDialog" id="shareDlg">' +
        '<div class="shareDlgTitle">作者：</div>' +
        '<input type="text" tipMsg="你的名字" class="shareAuthorInput" value="' + userName + '"></input>' +
        '<div class="shareDlgTitle">作品介绍：</div>' +
        '<textarea placeholder="简单介绍下你的游戏吧" rows="3" class="shareDes" id="shareDesText">' + projectDes + '</textarea>' +
        '<div class="shareDlgTitle">操作方法：</div>' +
        '<textarea placeholder="请简单描述下，大家要怎么玩这个游戏吧" rows="3" class="shareDes" id="shareUsageText">' + usageDes + '</textarea>' +
        '<button id="shareConfirmBtn" class="shareConfirm" onclick="return false;">确定分享</button>' +
        '</div>';

      //关闭上传功能对话框
      $('#dialog').css('display', 'none');

      //打开信息框
      layer.open({
        type: 1,
        title: ['分享内容', 'text-align: center;font-weight:bold;'],
        shadeClose: false, //点击遮罩关闭
        content: content
      });

      //生成input的提示
      inputTipText();

      $('#shareConfirmBtn').on('click', function () {
        shareConfirm();
      });

    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert('啊哦，失败了，再试一下吧');
    }
  });

};

//初始化Input的灰色提示信息
function inputTipText() {
  $('input[tipMsg]').each(function () {
    if ($(this).val() == '') {
      var oldVal = $(this).attr('tipMsg');
      if ($(this).val() == '') {
        $(this).attr('value', oldVal).css({'color': '#888'});
      }
      $(this)
        .css({'color': '#888'})     //灰色
        .focus(function () {
          if ($(this).val() != oldVal) {
            $(this).css({'color': '#000'});
          } else {
            $(this).val('').css({'color': '#888'});
          }
        })
        .blur(function () {
          if ($(this).val() == '') {
            $(this).val(oldVal).css({'color': '#888'});
          }
        })
        .keydown(function () {
          $(this).css({'color': '#000'});
        });
    }
  });
}

//加载项目列表
function projectList() {
  window.dialog('projectList');
}

//从url载入项目
function loadProject(url) {
  if (url) {
    console.log('load project:' + url);
    window.scratch.loadProject(url);
  }
}

//对话框

window.dialog = function (type, title = null, content = null) {
  switch (type) {
  case 'tip':
    layer.alert(content,{title:title});
    break;
  }
};

window.onbeforeunload = function (event) {
  return '真的要关闭嘛？';
};

//JS抛异常
function JSthrowError(e) {
  if (window.onerror) {
    window.onerror(e, 'swf', 0);
  } else {
    console.error(e);
  }
}

//拖拽
(function ($) {
  $.fn.dragDiv = function (divWrap) {
    return this.each(function () {
      var $divMove = $(this); //鼠标可拖拽区域
      var $divWrap = divWrap ? $divMove.parents(divWrap) : $divMove; //整个移动区域
      var mX = 0;
      var mY = 0; //定义鼠标X轴Y轴
      var dX = 0;
      var dY = 0; //定义div左、上位置
      var isDown = false; //mousedown标记
      var mouseDown = function (event) {
        event = event || window.event;
        mX = event.clientX;
        mY = event.clientY;
        dX = $divWrap.offset().left;
        dY = $divWrap.offset().top;
        isDown = true; //鼠标拖拽启动
      };
      var mouseMove = function (event) {
        event = event || window.event;
        var x = event.clientX; //鼠标滑动时的X轴
        var y = event.clientY; //鼠标滑动时的Y轴
        if (isDown) {
          $divWrap.css({
            'left': x - mX + dX,
            'top': y - mY + dY
          }); //div动态位置赋值
        }
      };
      var mouseUp = function () {
        isDown = false; //鼠标拖拽结束
      };
      if (document.attachEvent) { //ie的事件监听，拖拽div时禁止选中内容，firefox与chrome已在css中设置过-moz-user-select: none; -webkit-user-select: none;
        $divMove[0].attachEvent('onselectstart', function () {
          return false;
        });
      }
      $divMove.mousedown(mouseDown);
      $(document).mousemove(mouseMove);
      $divMove.mouseup(mouseUp);
    });
  };
})(jQuery);
$(document).ready(function () {
  $('#dialog').dragDiv();
});


//点击确定分享按钮
function shareConfirm() {
  layer.closeAll();
  var userId = window.user_id;
  var userName = $('.shareAuthorInput').val();
  var projectName = window.project_id;
  var projectTitle = window.user_id + '-' + window.project_id;
  var projectDes = $('#shareDesText').val();
  var projectUsage = $('#shareUsageText').val();

  var request = {
    type: 'shareMsgConfirm',
    userId: userId,
    userName: userName,
    projectName: projectName,
    projectTitle: projectTitle,
    projectDes: projectDes,
    projectUsage: projectUsage
  };

  $.ajax({
    url: '/scratch/share_confirm/' + window.project_id,
    type: 'POST',
    data: request,
    success: function (json, status) {//如果调用php成功
      var success = json.status;
      if (!success) {
        alert('插入信息失败，请联系老师解决！谢谢');
        return;
      }
      // //打开信息框
      layer.open({
        type: 1,
        title: ['扫码分享', 'text-align: center;font-weight:bold;'],
        shadeClose: false, //点击遮罩关闭
        content: '<div class="shareDialog" id="shareDlg">'
        + '<div id="code" class="code"></div>'
        + '<div class ="sharetext">使用微信或QQ扫码分享</div>'
        + '</div>'
      });
      var url = GetUrlPath();
      var fileName = window.fileName;
      var shareAddress = url + '/scratch_share/project/' + window.project_id + '?file=' + fileName +
        '&userId=' + userId + '&project=' + projectName;
      $('#code').qrcode({render: 'canvas', width: 150, height: 150, correctLevel: 0, text: shareAddress});

    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert('复制文件出错，请联系老师解决！谢谢');
    }
  });

  function GetUrlPath() {
    var url = window.location.protocol + '//' + window.location.host;
    return url;
  }
}


function getQueryVariable(variable) {
  var query = window.location.search.substring(1);
  var vars = query.split('&');
  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split('=');
    if (pair[0] == variable) {
      return pair[1];
    }
  }
  return (false);
}

$.ajaxSetup({cache: false});
$.when(window.SWFready).done(function () {
  console.log('getProject');
  window.scratch.loadProject(window.file_url);
});

//提示保存
reminderSave();

//用于标识用户是不是点击了分享作品按钮
window.share = false;
//Attention scratch-online要与scratch-h5在同一级目录
$('.invent').click(function () {
  window.scratch.saveProject();
  window.share = true;
  //生成二维码的代码在scratch.js的uploaded函数中。
});

window.published = false;
$('.published').click(function () {
  window.scratch.saveProject();
  window.published = true;
});

function reminderSave() {
  layer.link('../../../libs/layui-layer.css');
  // //打开信息框
  layer.open({
    type: 1,
    area: ['600px', '360px'],
    shadeClose: true, //点击遮罩关闭
    content: '<div class="remindSave">'
    + '<div class ="sharetext">编辑后记得保存哦！</div>'
    + '<img src="/assets/libs/scratch/css/img/save.gif" class="remindImg"></img>'
    + '</div>'
  });

}