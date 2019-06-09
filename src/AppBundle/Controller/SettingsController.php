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

        return $this->redirectToRoute('settings_security'
        );
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
