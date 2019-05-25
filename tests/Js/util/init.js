import process from 'process';
import decache from 'decache';
import store from 'store';

const getRootPath = function(){
  return process.cwd();
};

const Translator = {
  trans: function(value){
    return value;
  }
};

const init = function(html = '', options)
{
  let { JSDOM } = require('jsdom');
  options = Object.assign({
    url: 'http://demo.edusoho.com/',
    referrer: 'http://demo.edusoho.com/',
    contentType: 'text/html',
    userAgent: 'Mellblomenator/9000',
    includeNodeLocations: true,
  }, options);

  let dom = new JSDOM(`<!DOCTYPE html><html><body>${html}</body></html>`, options);
  global.window = dom.window;
  global.document = window.document;
  
  decache('jquery');
  let $ = require('jquery');
  $.validator = $.fn.validate = $.fn.rules = $.fn.datetimepicker = function(data){
  };
  $.validator.addMethod = function(name, fn) {
  };

  global.$ = $;
  global.jQuery = $;
  global.store = store;
  global.Translator = Translator;
  global.navigator = window.navigator;
};

init();

export {
  init,
  getRootPath
};
