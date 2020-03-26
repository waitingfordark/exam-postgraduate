import 'jquery-validation';
import { isEmpty } from 'common/utils';
import axis from 'common/axis';

$.validator.setDefaults({
  errorClass: 'form-error-message jq-validate-error',
  errorElement: 'p',
  onkeyup: false,
  ignore: '',
  ajax: false,
  currentDom: null,
  highlight: function(element, errorClass, validClass) {
    let $row = $(element).addClass('form-control-error').closest('.form-group').addClass('has-error');
    $row.find('.help-block').hide();
  },
  unhighlight: function(element, errorClass, validClass) {
    let $row = $(element).removeClass('form-control-error').closest('.form-group');
    $row.removeClass('has-error');
    $row.find('.help-block').show();
  },
  errorPlacement: function(error, element) {
    if (element.parent().hasClass('controls')) {
      element.parent('.controls').append(error);
    } else if (element.parent().hasClass('input-group')) {
      element.parent().after(error);
    } else if (element.parent().is('label')) {
      element.parent().parent().append(error);
    } else {
      element.parent().append(error);
    }
  },
  invalidHandler: function(data, validator) {
    const errorNum = validator.numberOfInvalids();
    if (errorNum) {
      $(validator.errorList[0].element).focus();
    }
    console.log(data);
  },
  submitError: function(data) {
    console.log('submitError');
  },
  submitSuccess: function(data) {
    console.log('submitSuccess');
  },
  submitHandler: function(form) {
    console.log('submitHandler');
  
    let $form = $(form);
    let settings = this.settings;
    let $btn = $(settings.currentDom);
    if (!$btn.length) {
      $btn = $(form).find('[type="submit"]');
    }
    $btn.button('loading');
    if (settings.ajax) {
      $.post($form.attr('action'), $form.serializeArray(), (data) => {
        $btn.button('reset');
        settings.submitSuccess(data);
      }).error((data) => {
        $btn.button('reset');
        settings.submitError(data);
      });
    } else {
      form.submit();
    }
  }
});

$.extend($.validator.prototype, {
  defaultMessage: function(element, rule) {
    if (typeof rule === 'string') {
      rule = { method: rule };
    }

    var message = this.findDefined(
        this.customMessage(element.name, rule.method),
        this.customDataMessage(element, rule.method),

        // 'title' is never undefined, so handle empty string as undefined
        !this.settings.ignoreTitle && element.title || undefined,
        $.validator.messages[rule.method],
        '<strong>Warning: No message defined for ' + element.name + '</strong>'
      ),
      theregex = /\$?\{(\d+)\}/g,
      displayregex = /%display%/g;
    if (typeof message === 'function') {
      message = message.call(this, rule.parameters, element);
    } else if (theregex.test(message)) {
      message = $.validator.format(message.replace(theregex, '{$1}'), rule.parameters);
    }

    if (displayregex.test(message)) {
      var labeltext, name;
      var id = $(element).attr('id') || $(element).attr('name');
      if (id) {
        labeltext = $('label[for=' + id + ']').text();
        if (labeltext) {
          labeltext = labeltext.replace(/^[\*\s\:\：]*/, '').replace(/[\*\s\:\：]*$/, '');
        }
      }

      name = $(element).data('display') || $(element).attr('name');
      message = message.replace(displayregex, labeltext || name);
    }

    return message;
  }

});

$.extend($.validator.messages, {
  required: Translator.trans('validate.required.message'),

  email: Translator.trans('validate.valid_email_input.message'),
  url: Translator.trans('validate.valid_url_input.message'),
  date: Translator.trans('validate.valid_date_input.message'),
  dateISO: Translator.trans('validate.valid_date_iso_input.message'),
  number: Translator.trans('validate.valid_number_input.message'),
  digits: Translator.trans('validate.valid_digits_input.message'),
  creditcard: Translator.trans('validate.valid_creditcard_input.message'),
  equalTo: Translator.trans('validate.valid_equal_to_input.message'),
  extension: Translator.trans('validate.valid_extension_input.message'),
  maxlength: $.validator.format(Translator.trans('validate.max_length.message')),
  minlength: $.validator.format(Translator.trans('validate.min_length.message')),
  rangelength: $.validator.format(Translator.trans('validate.range_length.message')),
  range: $.validator.format(Translator.trans('validate.range.message')),
  max: $.validator.format(Translator.trans('validate.max.message')),
  min: $.validator.format(Translator.trans('validate.min.message'))
});




$.validator.addMethod('trim', function(value, element, params) {
  return this.optional(element) || $.trim(value).length > 0;
}, Translator.trans('validate.trim.message'));

$.validator.addMethod('visible_character', function(value, element, params) {
  return this.optional(element) || (value.match(/\S/g).length === value.length);
}, Translator.trans('validate.visible_character.message'));

$.validator.addMethod('idcardNumber', function(value, element, params) {
  let _check = function(idcardNumber) {
    let reg = /^\d{17}[0-9xX]$/i;
    if (!reg.test(idcardNumber)) {
      return false;
    }
    let n = new Date();
    let y = n.getFullYear();
    if (parseInt(idcardNumber.substr(6, 4)) < 1900 || parseInt(idcardNumber.substr(6, 4)) > y) {
      return false;
    }
    let birth = idcardNumber.substr(6, 4) + '-' + idcardNumber.substr(10, 2) + '-' + idcardNumber.substr(12, 2);
    if (!'undefined' == typeof birth.getDate) {
      return false;
    }
    let IW = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1];
    let iSum = 0;
    for (let i = 0; i < 17; i++) {
      iSum += parseInt(idcardNumber.charAt(i)) * IW[i];
    }
    let iJYM = iSum % 11;
    let sJYM = '';
    if (iJYM == 0) sJYM = '1';
    else if (iJYM == 1) sJYM = '0';
    else if (iJYM == 2) sJYM = 'x';
    else if (iJYM == 3) sJYM = '9';
    else if (iJYM == 4) sJYM = '8';
    else if (iJYM == 5) sJYM = '7';
    else if (iJYM == 6) sJYM = '6';
    else if (iJYM == 7) sJYM = '5';
    else if (iJYM == 8) sJYM = '4';
    else if (iJYM == 9) sJYM = '3';
    else if (iJYM == 10) sJYM = '2';
    let cCheck = idcardNumber.charAt(17).toLowerCase();
    if (cCheck != sJYM) {
      return false;
    }
    return true;
  };
  return this.optional(element) || _check(value);
}, Translator.trans('validate.idcard_number_input.message'));


function calculateByteLength(string) {
  let length = string.length;
  for (let i = 0; i < string.length; i++) {
    if (string.charCodeAt(i) > 127)
      length++;
  }
  return length;
}