<?php

namespace AppBundle\Handler;

use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    public function onLogoutSuccess(Request $request)
    {
        $goto = $request->query->get('goto');

        if (!$goto) {
            $goto = 'login';
        }

        $this->targetUrl = $this->httpUtils->generateUri($request, $goto);

        if ($this->getAuthService()->hasPartnerAuth()) {
            $user = ServiceKernel::instance()->getCurrentUser();
            setcookie('REMEMBERME');

            if (!$user->isLogin()) {
                return parent::onLogoutSuccess($request);
            }

            $url = $this->httpUtils->generateUri($request, 'partner_logout');
            $queries = array('userId' => $user['id'], 'goto' => $this->targetUrl);
            $url = $url.'?'.http_build_query($queries);

            return $this->httpUtils->createRedirectResponse($request, $url);
        }

        setcookie('_last_logout_locale', $request->getSession()->get('_locale'), -1);
 
        return parent::onLogoutSuccess($request);
    }

    private function getAuthService()
    {
        return ServiceKernel::instance()->createService('User:AuthService');
    }

    public function isMicroMessenger($request)
    {
        return strpos($request->headers->get('User-Agent'), 'MicroMessenger') !== false;
    }

    protected function getSettingService()
    {
        return ServiceKernel::instance()->createService('System:SettingService');
    }
}
