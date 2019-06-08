<?php

namespace AppBundle\Controller;

use Biz\Content\Service\FileService;
use Biz\Sensitive\Service\SensitiveService;
use Biz\System\Service\LogService;
use Biz\System\Service\SettingService;
use Biz\User\Service\AuthService;
use Biz\User\Service\UserFieldService;
use AppBundle\Common\SmsToolkit;
use AppBundle\Common\CurlToolkit;
use AppBundle\Common\FileToolkit;
use Codeages\Biz\Pay\Service\AccountService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use AppBundle\Component\OAuthClient\OAuthClientFactory;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;

class SettingsController extends BaseController
{
    public function profileAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $profile = $this->getUserService()->getUserProfile($user['id']);

        $name = 250;

        $profile['title'] = $user['title'];

        if ('POST' === $request->getMethod()) {
            $profile = $request->request->get('profile');

            if (!((strlen($user['verifiedMobile']) > 0) && (isset($profile['mobile'])))) {
                $this->getUserService()->updateUserProfile($user['id'], $profile, false);

                $this->setFlashMessage('success', 'site.save.success');
            } else {
                $this->setFlashMessage('danger', 'user.settings.profile.unable_change_bind_mobile');
            }

            return $this->redirect($this->generateUrl('settings'));
        }

        $fields = $this->getUserFieldService()->getEnabledFieldsOrderBySeq();

        if (array_key_exists('idcard', $profile) && '0' == $profile['idcard']) {
            $profile['idcard'] = '';
        }

        $fromCourse = $request->query->get('fromCourse');

        return $this->redirectToRoute('settings_security'
            
        );

        return $this->render('settings/profile.html.twig', array(
            'profile' => $profile,
            'fields' => $fields,
            'fromCourse' => $fromCourse,
            'user' => $user,
        ));
    }

    public function approvalSubmitAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $profile = $this->getUserService()->getUserProfile($user['id']);
        $profile['idcard'] = substr_replace($profile['idcard'], '************', 4, 12);
        $approval = $this->getUserService()->getLastestApprovalByUserIdAndStatus($user['id'], $user['approvalStatus']);

        if ('POST' === $request->getMethod()) {
            $faceImg = $request->files->get('faceImg');
            $backImg = $request->files->get('backImg');

            if (abs(filesize($faceImg)) > 2 * 1024 * 1024 || abs(filesize($backImg)) > 2 * 1024 * 1024
                || !FileToolkit::isImageFile($backImg) || !FileToolkit::isImageFile($faceImg)) {
                $this->setFlashMessage('danger', 'user.settings.verification.photo_require_tips');

                return $this->render('settings/approval.html.twig', array(
                    'profile' => $profile,
                ));
            }

            $directory = $this->container->getParameter('topxia.upload.private_directory').'/approval';
            $this->getUserService()->applyUserApproval($user['id'], $request->request->all(), $faceImg, $backImg, $directory);

            return $this->redirect($this->generateUrl('setting_approval_submit'));
        }

        return $this->render('settings/approval.html.twig', array(
            'profile' => $profile,
            'approval' => $approval,
        ));
    }

    public function nicknameAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $isNickname = $this->getSettingService()->get('user_partner');
        if (0 == $isNickname['nickname_enabled']) {
            return $this->redirect($this->generateUrl('settings'));
        }

        if ('POST' === $request->getMethod()) {
            $nickname = $request->request->get('nickname');

            if ($this->getSensitiveService()->scanText($nickname)) {
                return $this->createJsonResponse(array('message' => 'user.settings.basic_info.illegal_nickname'), 403);
            }

            list($result, $message) = $this->getAuthService()->checkUsername($nickname);

            if ('success' !== $result && $user['nickname'] != $nickname) {
                return $this->createJsonResponse(array('message' => $message), 403);
            }

            $this->getAuthService()->changeNickname($user['id'], $nickname);

            return $this->createJsonResponse(array('message' => 'user.settings.basic_info.nickname_change_successfully'));
        }
    }

    public function nicknameCheckAction(Request $request)
    {
        $nickname = $request->query->get('value');
        $currentUser = $this->getUserService()->getCurrentUser();

        if ($currentUser['nickname'] == $nickname) {
            return $this->createJsonResponse(array('success' => true, 'message' => ''));
        }

        list($result, $message) = $this->getAuthService()->checkUsername($nickname);

        if ('success' === $result) {
            $response = array('success' => true, 'message' => '');
        } else {
            $response = array('success' => false, 'message' => $message);
        }

        return $this->createJsonResponse($response);
    }

    public function avatarCropModalAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();

        if ('POST' === $request->getMethod()) {
            $options = $request->request->all();
            $this->getUserService()->changeAvatar($currentUser['id'], $options['images']);
            $user = $this->getUserService()->getUser($currentUser['id']);
            $avatar = $this->getWebExtension()->getFpath($user['largeAvatar']);

            return $this->createJsonResponse(array(
                'status' => 'success',
                'avatar' => $avatar, ));
        }

        $fileId = $request->getSession()->get('fileId');
        list($pictureUrl, $naturalSize, $scaledSize) = $this->getFileService()->getImgFileMetaInfo($fileId, 270, 270);

        return $this->render('settings/avatar-crop-modal.html.twig', array(
            'pictureUrl' => $pictureUrl,
            'naturalSize' => $naturalSize,
            'scaledSize' => $scaledSize,
        ));
    }

    //传头像，新的交互
    public function profileAvatarCropModalAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();

        if ('POST' === $request->getMethod()) {
            $options = $request->request->all();
            $result = $this->getUserService()->changeAvatar($currentUser['id'], $options['images']);
            $image = $this->getWebExtension()->getFpath($result['largeAvatar']);

            return $this->createJsonResponse(array(
                'image' => $image,
            ), 200);
        }

        return $this->render('settings/profile-avatar-crop-modal.html.twig');
    }

    public function avatarFetchPartnerAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();

        if (!$this->getAuthService()->hasPartnerAuth()) {
            throw $this->createNotFoundException();
        }

        $url = $this->getAuthService()->getPartnerAvatar($currentUser['id'], 'big');

        if (empty($url)) {
            $this->setFlashMessage('danger', 'user.settings.avatar.fetch_form_partner_error');

            return $this->createJsonResponse(true);
        }

        $imgUrl = $request->request->get('imgUrl');
        $file = new File($this->downloadImg($imgUrl));
        $groupCode = 'tmp';
        $imgs = array(
            'large' => array('200', '200'),
            'medium' => array('120', '120'),
            'small' => array('48', '48'),
        );
        $options = array(
            'x' => '0',
            'y' => '0',
            'x2' => '200',
            'y2' => '200',
            'w' => '200',
            'h' => '200',
            'width' => '200',
            'height' => '200',
            'imgs' => $imgs,
        );

        if (empty($options['group'])) {
            $options['group'] = 'default';
        }

        $record = $this->getFileService()->uploadFile($groupCode, $file);
        $parsed = $this->getFileService()->parseFileUri($record['uri']);
        $filePaths = FileToolKit::cropImages($parsed['fullpath'], $options);

        $fields = array();

        foreach ($filePaths as $key => $value) {
            $file = $this->getFileService()->uploadFile($options['group'], new File($value));
            $fields[] = array(
                'type' => $key,
                'id' => $file['id'],
            );
        }

        if (isset($options['deleteOriginFile']) && 0 == $options['deleteOriginFile']) {
            $fields[] = array(
                'type' => 'origin',
                'id' => $record['id'],
            );
        } else {
            $this->getFileService()->deleteFileByUri($record['uri']);
        }

        $this->getUserService()->changeAvatar($currentUser['id'], $fields);

        return $this->createJsonResponse(true);
    }

    public function securityAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $profile = $this->getUserService()->getUserProfile($user['id']);

        $hasLoginPassword = strlen($user['password']) > 0;
        $hasEmail = strlen($user['email']) > 0 && false === stripos($user['email'], '@edusoho.net');

        $email = $hasEmail ? $user['email'] : '';
        $hasVerifiedEmail = $user['emailVerified'];

        return $this->render('settings/security.html.twig', array(
            'hasLoginPassword' => $hasLoginPassword,
            'hasEmail' => $hasEmail,
            'email' => $email,
            'hasVerifiedEmail' => $hasVerifiedEmail,
            'user' => $user,
            'profile' => $profile
        ));
    }

    public function setPasswordAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $form = $this->createFormBuilder()
            ->add('newPassword', 'password')
            ->add('confirmPassword', 'password')
            ->getForm();

        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $passwords = $form->getData();
                $this->getUserService()->changePassword($user['id'], $passwords['newPassword']);
                $form = $this->createFormBuilder()
                    ->add('currentUserLoginPassword', 'password')
                    ->add('newPayPassword', 'password')
                    ->add('confirmPayPassword', 'password')
                    ->getForm();

                return $this->render('settings/pay-password-modal.html.twig', array(
                    'form' => $form->createView(),
                ));
            }
        }

        return $this->render('settings/password-modal.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function passwordAction(Request $request)
    {
        $user = $this->getCurrentUser();

        if ($user->isLogin() && empty($user['password'])) {
            return $this->redirect($this->generateUrl('settings_setup_password', array('targetPath' => 'settings_password')));
        }

        if ('POST' === $request->getMethod()) {
            $passwords = $request->request->all();
            $validatePassed = $this->getAuthService()->checkPassword($user['id'], $passwords['currentPassword']);

            if (!$validatePassed) {
                return $this->createJsonResponse(array('message' => 'user.settings.security.password_modify.incorrect_password'), 403);
            } else {
                $this->getUserService()->initPassword($user['id'], $passwords['newPassword']);

                return $this->createJsonResponse(array('message' => 'site.modify.success'));
            }
        }

        return $this->render('settings/password.html.twig');
    }

    public function emailAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $mailer = $this->getSettingService()->get('mailer', array());
        $cloudEmail = $this->getSettingService()->get('cloud_email_crm', array());

        if ($user->isLogin() && empty($user['password'])) {
            return $this->redirect($this->generateUrl('settings_setup_password', array('targetPath' => 'settings_email')));
        }

        if ('POST' === $request->getMethod()) {
            $data = $request->request->all();

            $isPasswordOk = $this->getUserService()->verifyPassword($user['id'], $data['password']);

            if (!$isPasswordOk) {
                return $this->createJsonResponse(array('message' => 'site.incorrect.password'), 403);
            }

            $userOfNewEmail = $this->getUserService()->getUserByEmail($data['email']);

            if ($userOfNewEmail && $userOfNewEmail['id'] == $user['id']) {
                return $this->createJsonResponse(array('message' => 'user.settings.email.new_email_same_old'), 403);
            }

            if ($userOfNewEmail && $userOfNewEmail['id'] != $user['id']) {
                return $this->createJsonResponse(array('message' => 'user.settings.email.new_email_not_unique'), 403);
            }

            $tokenArgs = array(
                'userId' => $user['id'],
                'duration' => 60 * 60 * 24,
                'data' => $data['email'],
            );

            $token = $this->getTokenService()->makeToken('email-verify', $tokenArgs);
            $token = $token['token'];
            try {
                $site = $this->setting('site', array());
                $mailOptions = array(
                    'to' => $data['email'],
                    'template' => 'email_reset_email',
                    'params' => array(
                        'sitename' => $site['name'],
                        'siteurl' => $site['url'],
                        'verifyurl' => $this->generateUrl('auth_email_confirm', array('token' => $token), true),
                        'nickname' => $user['nickname'],
                    ),
                );
                $mailFactory = $this->getBiz()->offsetGet('mail_factory');
                $mail = $mailFactory($mailOptions);
                $mail->send();

                return $this->render('settings/email-verfiy.html.twig',
                    array(
                        'message' => $this->get('translator')->trans('user.settings.email.send_success', array('%email%' => $data['email'])),
                        'data' => array(
                            'email' => $data['email'],
                        ),
                ));
            } catch (\Exception $e) {
                $this->getLogService()->error('system', 'setting_email_change', '邮箱变更确认邮件发送失败:'.$e->getMessage());

                return $this->createJsonResponse(array('message' => 'user.settings.email.send_error'), 403);
            }
        }

        return $this->render('settings/email.html.twig', array(
            'mailer' => $mailer,
            'cloudEmail' => $cloudEmail,
        ));
    }

    public function setupPasswordAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $targetPath = $request->query->get('targetPath');
        $showType = $request->query->get('showType', 'modal');
        $form = $this->createFormBuilder()
            ->add('newPassword', 'password')
            ->add('confirmPassword', 'password')
            ->getForm();

        if ('POST' === $request->getMethod()) {
            if (!empty($user['password'])) {
                return $this->createJsonResponse(array(
                    'message' => 'user.settings.login_password_fail',
                ), 500);
            }
            $form->bind($request);
            if ($form->isValid()) {
                $passwords = $form->getData();
                $this->getUserService()->changePassword($user['id'], $passwords['newPassword']);

                return $this->createJsonResponse(array(
                    'message' => 'user.settings.login_password_success',
                ));
            } else {
                return $this->createJsonResponse(array(
                    'message' => 'user.settings.login_password_fail',
                ), 500);
            }
        }

        return $this->render('settings/setup-password.html.twig', array(
            'targetPath' => $targetPath,
            'showType' => $showType,
            'form' => $form->createView(),
        ));
    }

    public function setupCheckNicknameAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $nickname = $request->query->get('value');

        if ($nickname == $user['nickname']) {
            $response = array('success' => true);
        } else {
            list($result, $message) = $this->getAuthService()->checkUsername($nickname);

            if ('success' === $result) {
                $response = array('success' => true);
            } else {
                $response = array('success' => false, 'message' => $message);
            }
        }

        return $this->createJsonResponse($response);
    }

    protected function checkBindsName($type)
    {
        $types = array_keys(OAuthClientFactory::clients());

        if (!in_array($type, $types)) {
            throw new NotFoundException('Type Not Found');
        }
    }

    protected function createOAuthClient($type)
    {
        $settings = $this->setting('login_bind');

        if (empty($settings)) {
            throw new \RuntimeException('第三方登录系统参数尚未配置，请先配置。');
        }

        if (empty($settings) || !isset($settings[$type.'_enabled']) || empty($settings[$type.'_key']) || empty($settings[$type.'_secret'])) {
            throw new \RuntimeException('第三方登录('.$type.')系统参数尚未配置，请先配置。');
        }

        if (!$settings[$type.'_enabled']) {
            throw new \RuntimeException('第三方登录('.$type.')未开启');
        }

        $config = array('key' => $settings[$type.'_key'], 'secret' => $settings[$type.'_secret']);
        $client = OAuthClientFactory::create($type, $config);

        return $client;
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->getBiz()->service('Content:FileService');
    }

    /**
     * @return AuthService
     */
    protected function getAuthService()
    {
        return $this->getBiz()->service('User:AuthService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('System:SettingService');
    }

    /**
     * @return UserFieldService
     */
    protected function getUserFieldService()
    {
        return $this->getBiz()->service('User:UserFieldService');
    }

    /**
     * @return \Biz\User\Service\TokenService
     */
    protected function getTokenService()
    {
        return $this->getBiz()->service('User:TokenService');
    }

    /**
     * @return SensitiveService
     */
    protected function getSensitiveService()
    {
        return $this->getBiz()->service('Sensitive:SensitiveService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->getBiz()->service('System:LogService');
    }

    /**
     * @return AccountService
     */
    protected function getAccountService()
    {
        return $this->getBiz()->service('Pay:AccountService');
    }

    protected function downloadImg($url)
    {
        $currentUser = $this->getCurrentUser();
        //        $filename    = md5($url).'_'.time();
        $filePath = $this->container->getParameter('topxia.upload.public_directory').'/tmp/'.$currentUser['id'].'_'.time().'.jpg';

        $fp = fopen($filePath, 'w');
        $img = fopen($url, 'r');
        stream_get_meta_data($img);
        $result = '';
        while (!feof($img)) {
            $result .= fgets($img, 1024);
        }

        fclose($img);
        fwrite($fp, $result);
        fclose($fp);

        return $filePath;
    }
}
