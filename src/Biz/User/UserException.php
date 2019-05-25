<?php

namespace Biz\User;

use AppBundle\Common\Exception\AbstractException;

class UserException extends AbstractException
{
    const EXCEPTION_MODUAL = 01;

    const UN_LOGIN = 4040101;

    const LIMIT_LOGIN = 4030102;

    const FORBIDDEN_REGISTER = 4030103;

    const NOTFOUND_USER = 4040104;

    const ERROR_RESET_PASSWORD_EMAIL = 5000105;

    const FORBIDDEN_DISCUZ_USER_RESET_PASSWORD = 4030106;

    const ERROR_MOBILE_REGISTERED = 4030107;

    const FORBIDDEN_REGISTER_LIMIT = 4030108;

    const FORBIDDEN_SEND_MESSAGE = 4030110;

    const UPDATE_NICKNAME_ERROR = 5000111;

    const NICKNAME_INVALID = 5000112;

    const NICKNAME_EXISTED = 5000113;

    public $messages = array(
        4040101 => 'exception.user.unlogin',
        4030102 => 'exception.user.unlogin',
        4030103 => 'exception.user.register_error',
        4040104 => 'exception.user.not_found',
        5000105 => 'exception.user.reset_password_email_send',
        4030106 => 'exception.user.discuz_user_reset_password',
        4030107 => 'exception.user.mobile_registered',
        4030108 => 'exception.user.register_limit',
        4030110 => 'exception.user.message_forbidden',
        5000111 => 'exception.user.update_nickname_error',
        5000112 => 'exception.user.nickname_invalid',
        5000113 => 'exception.user.nickname_existed',
    );
}
