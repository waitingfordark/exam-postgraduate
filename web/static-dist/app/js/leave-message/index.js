webpackJsonp(["app/js/leave-message/index"],{0:function(e,a){e.exports=jQuery},"11f73426d516e21cafbe":function(e,a,s){"use strict";var n=s("b334fd7e4c5a19234db2"),r=function(e){return e&&e.__esModule?e:{default:e}}(n),t=$("#leave-message-form"),u=t.validate({rules:{name:{maxlength:32,required:!0},email:{required:!1,email:!0},phone:{required:!0,phone:!0}},messages:{}});$("#leave-message-commit").click(function(e){u&&u.form()&&((0,r.default)("success","提交成功"),$(e.currentTarget).button("loading"),t.submit())})}},["11f73426d516e21cafbe"]);