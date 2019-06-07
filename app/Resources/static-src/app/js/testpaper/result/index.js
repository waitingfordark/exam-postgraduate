import DoTestBase from '../widget/do-test-base';
import { 
  initScrollbar,
  testpaperCardLocation,
  onlyShowError } from '../widget/part';

initScrollbar();
testpaperCardLocation();
onlyShowError();

let doBase = new DoTestBase($('.js-task-testpaper-body'));
clearInterval(doBase.$usedTimer);

$('.js-testpaper-redo-timer').timer({
  countdown:true,
  duration: $('.js-testpaper-redo-timer').data('time'),
  format: '%H:%M:%S',
  callback: function() {
    $('#finishPaper').attr('disabled',false);
  },
  repeat: true,
  start: function() {
    self.usedTime = 0;
  }
});

$('#finishPaper').click(function() {
  if ($(this).attr('disabled') == 'disabled') {
    return false;
  } else {
    return true;
  }
});
