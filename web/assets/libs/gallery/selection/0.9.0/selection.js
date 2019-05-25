define("gallery/selection/0.9.0/selection",[],function(e,t,r){function n(e,t){return this.element=e,this.cursor=function(e,r){var n=this.element;if(e===void 0)return t?i(n):[n.selectionStart,n.selectionEnd];if(v(e)){var o=e;e=o[0],r=o[1]}return r===void 0&&(r=e),t?a(n,e,r):n.setSelectionRange(e,r),this},this}function o(e){if(e)this.text=function(){return document.selection.createRange().text};else{var t=window.getSelection();this.element=u(t),this.text=function(){return""+t}}return this}function i(e){var t=document.selection.createRange();if(t&&t.parentElement()===e){var r,n,o=e.value.replace(/\r\n/g,"\n"),i=o.length,a=e.createTextRange();a.moveToBookmark(t.getBookmark());var c=e.createTextRange();return c.collapse(!1),a.compareEndPoints("StartToEnd",c)>-1?r=n=i:(r=-a.moveStart("character",-i),n=-a.moveEnd("character",-i)),r>n&&(n=i),[r,n]}return[0,0]}function a(e,t,r){var n=e.createTextRange();n.move("character",t),n.moveEnd("character",r-t),n.select()}function c(e,t,r,n,o){t===void 0&&(t="");var i=e.element.value;return e.element.value=[i.slice(0,r),t,i.slice(n)].join(""),n=r+t.length,"left"===o?e.cursor(r):"right"===o?e.cursor(n):e.cursor(r,n),e}function u(e){for(var t=null,r=e.anchorNode,n=e.focusNode;!t;){if(r.parentElement===n.parentElement){t=n.parentElement;break}r=r.parentElement,n=n.parentElement}return t}var s=function(e){if(e&&e.length&&(e=e[0]),e){if(e.selectionStart!==void 0)return new n(e);var t=e.tagName.toLowerCase()}if(t&&("textarea"===t||"input"===t))return new n(e,!0);if(window.getSelection)return new o;if(document.selection)return new o(!0);throw Error("your browser is very weird")};s.version="<%= pkg.version %>",r.exports=s,n.prototype.text=function(e,t){var r=this.element,n=this.cursor();return e===void 0?r.value.slice(n[0],n[1]):c(this,e,n[0],n[1],t)},n.prototype.append=function(e,t){var r=this.cursor()[1];return c(this,e,r,r,t)},n.prototype.prepend=function(e,t){var r=this.cursor()[0];return c(this,e,r,r,t)},n.prototype.surround=function(e){e===void 0&&(e=1);var t=this.element.value,r=this.cursor(),n=t.slice(Math.max(0,r[0]-e),r[0]),o=t.slice(r[1],r[1]+e);return[n,o]},n.prototype.line=function(){var e=this.element.value,t=this.cursor(),r=e.slice(0,t[0]).lastIndexOf("\n"),n=e.slice(t[1]).indexOf("\n"),o=r+1;if(-1===n)return e.slice(o);var i=t[1]+n;return e.slice(o,i)};var l=Object.prototype.toString,v=Array.isArray;v||(v=function(e){return"[object Array]"===l.call(e)})});
