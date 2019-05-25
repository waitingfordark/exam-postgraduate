$('body').on('click', '.es-qrcode', (event) => {
  const $target = $(event.currentTarget);
  if ($target.hasClass('open')) {
    $target.removeClass('open');
  } else {
    $.ajax({
      type: 'post',
      url: $target.data('url'),
      dataType: 'json',
      success: (data) => {
        $target.find('.qrcode-popover img').attr('src', data.img);
        $target.addClass('open');
      }
    });
  }
});

$('body').on('click', () => {
  const $qrcode = $('.es-qrcode');
  if ($qrcode.hasClass('open')) {
    $qrcode.removeClass('open');
  }
});