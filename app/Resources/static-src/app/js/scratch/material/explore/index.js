import notify from 'common/notify';

let $loginModal = $('#login-modal');
$('.js-exchange-btn').on('click', function () {
  if (!confirm(Translator.trans('material.exchange.confirm_message'))) {
    return;
  }

  let $this = $(this);
  $.post($this.data('url'), function (result) {
    if (result.message == 'json_response.not_login.message') {
      $loginModal.modal('show');
      $.get($loginModal.data('url'), function (html) {
        $loginModal.html(html);
      });
    } else {
      notify(result.status, Translator.trans(result.message));
    }

    if (result.status == 'success') {
      $('.js-my-reward-point').text($('.js-my-reward-point').text() - $this.data('price'));
      $this.html('已兑换');
    }
  });
});

$('.js-scratch-search-btn').on('click', function () {
  let url = $(this).data('url') + $('.js-scratch-search-input').val();
  window.location.href = url;
});

$('.js-scratch-search-input').on('keyup', function (e) {
  if (e.keyCode == 13) {
    let url = $('.js-scratch-search-btn').data('url') + $(this).val();
    window.location.href = url;
  }
});