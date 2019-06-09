<?php

namespace AppBundle\Controller;

use AppBundle\Common\ArrayToolkit;
use Biz\Theme\Service\ThemeService;
use Biz\Content\Service\BlockService;
use Biz\Course\Service\CourseService;
use Biz\CloudPlatform\CloudAPIFactory;
use Biz\System\Service\SettingService;
use Biz\Course\Service\CourseSetService;
use Biz\CloudPlatform\Service\AppService;
use Biz\Taxonomy\Service\CategoryService;
use Biz\Content\Service\NavigationService;
use Biz\Classroom\Service\ClassroomService;
use Symfony\Component\HttpFoundation\Request;
use Biz\User\Service\BatchNotificationService;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    public function indexAction(Request $request)
    {
        $user = $this->getCurrentUser();

        return $this->redirect($this->generateUrl('my_courses_learning'));
    }

    public function topNavigationAction($siteNav = null, $isMobile = false)
    {
        $navigations = $this->getNavigationService()->getOpenedNavigationsTreeByType('top');

        return $this->render('default/top-navigation.html.twig', array(
            'navigations' => $navigations,
            'siteNav' => $siteNav,
            'isMobile' => $isMobile,
        ));
    }

    public function translateAction(Request $request)
    {
        $locale = $request->query->get('language');
        $targetPath = $request->query->get('_target_path');

        $request->getSession()->set('_locale', $locale);

        $currentUser = $this->getCurrentUser();

        if ($currentUser->isLogin()) {
            $this->getUserService()->updateUserLocale($currentUser['id'], $locale);
        }

        return $this->redirectSafely($targetPath);
    }

    public function clientTimeCheckAction(Request $request)
    {
        $clientTime = $request->request->get('clientTime');
        $clientTime = strtotime($clientTime);

        if ($clientTime < time()) {
            return $this->createJsonResponse(false);
        }

        return $this->createJsonResponse(true);
    }


    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('System:SettingService');
    }

    /**
     * @return NavigationService
     */
    protected function getNavigationService()
    {
        return $this->getBiz()->service('Content:NavigationService');
    }

    /**
     * @return BlockService
     */
    protected function getBlockService()
    {
        return $this->getBiz()->service('Content:BlockService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    protected function getReviewService()
    {
        return $this->getBiz()->service('Course:ReviewService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->getBiz()->service('Taxonomy:CategoryService');
    }

    /**
     * @return AppService
     */
    protected function getAppService()
    {
        return $this->getBiz()->service('CloudPlatform:AppService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->getBiz()->service('Classroom:ClassroomService');
    }

    /**
     * @return BatchNotificationService
     */
    protected function getBatchNotificationService()
    {
        return $this->getBiz()->service('User:BatchNotificationService');
    }

    /**
     * @return ThemeService
     */
    protected function getThemeService()
    {
        return $this->getBiz()->service('Theme:ThemeService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->getBiz()->service('Course:CourseSetService');
    }

    protected function getCourseMemberService()
    {
        return $this->getBiz()->service('Course:MemberService');
    }

    protected function getTaskService()
    {
        return $this->getBiz()->service('Task:TaskService');
    }
}
