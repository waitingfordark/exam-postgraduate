import {
  initScrollbar,
  testpaperCardLocation,
} from 'app/js/testpaper/widget/part';

initScrollbar();
testpaperCardLocation();


$('.js-analysis').click(function(){
  let self = $(this);
  self.addClass('hidden');
  self.siblings('.js-analysis.hidden').removeClass('hidden');
  self.closest('.js-testpaper-question').find('.js-testpaper-question-analysis').slideToggle();
});
