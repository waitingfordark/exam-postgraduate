<?php

namespace AppBundle\Handler;

use Biz\Role\Util\PermissionBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Topxia\Service\Common\ServiceKernel;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Custom login listener.
 */
class LoginSuccessHandler
{
    /**
     * @var AuthorizationChecker
     */
    private $checker;

    /**
     * Constructor.
     *
     * @param AuthorizationChecker $checker
     * @param Doctrine             $doctrine
     */
    public function __construct(AuthorizationChecker $checker)
    {
        $this->checker = $checker;
    }

    /**
     *
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $user->setPermissions(PermissionBuilder::instance()->getPermissionsByRoles($user->getRoles()));

        $request = $event->getRequest();
        $sessionId = $request->getSession()->getId();
        $request->getSession()->set('loginIp', $request->getClientIp());

        $this->getUserService()->rememberLoginSessionId($user['id'], $sessionId);
    }

    private function getUserService()
    {
        return ServiceKernel::instance()->createService('User:UserService');
    }
}
