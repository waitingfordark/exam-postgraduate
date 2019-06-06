<?php

namespace AppBundle\Controller;

use Biz\RewardPoint\Service\AccountService;
use Biz\User\CurrentUser;
use AppBundle\Common\Paginator;
use Vip\Service\Vip\VipService;
use Biz\User\Service\AuthService;
use Biz\User\Service\UserService;
use Vip\Service\Vip\LevelService;
use AppBundle\Common\ArrayToolkit;
use Biz\Group\Service\GroupService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\Course\Service\ThreadService;
use Biz\System\Service\SettingService;
use Biz\User\Service\UserFieldService;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\CourseNoteService;
use Biz\User\Service\NotificationService;
use Biz\Classroom\Service\ClassroomService;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\SmsToolkit;

class UserController extends BaseController
{
    public function headerBlockAction($user)
    {
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $user = array_merge($user, $userProfile);

        if ($this->getCurrentUser()->isLogin()) {
            $isFollowed = $this->getUserService()->isFollowed($this->getCurrentUser()->id, $user['id']);
        } else {
            $isFollowed = false;
        }

        // 关注数
        $following = $this->getUserService()->findUserFollowingCount($user['id']);
        // 粉丝数
        $follower = $this->getUserService()->findUserFollowerCount($user['id']);

        //custom 积分
        $account = $this->getAccountService()->getAccountByUserId($user['id']);

        return $this->render('user/header-block.html.twig', array(
            'user' => $user,
            'isFollowed' => $isFollowed,
            'following' => $following,
            'follower' => $follower,
            'account' => $account,
        ));
    }

    public function showAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $userProfile['about'] = strip_tags($userProfile['about'], '');
        $userProfile['about'] = preg_replace('/ /', '', $userProfile['about']);
        $user = array_merge($user, $userProfile);

        if (in_array('ROLE_TEACHER', $user['roles'])) {
            return $this->_teachAction($user);
        }

        return $this->_learnAction($user);
    }


    public function learnAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $userProfile['about'] = strip_tags($userProfile['about'], '');
        $userProfile['about'] = preg_replace('/ /', '', $userProfile['about']);
        $user = array_merge($user, $userProfile);

        return $this->_learnAction($user);
    }


    public function teachAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $userProfile['about'] = strip_tags($userProfile['about'], '');
        $userProfile['about'] = preg_replace('/ /', '', $userProfile['about']);
        $user = array_merge($user, $userProfile);

        return $this->_teachAction($user);
    }

    public function learningAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $userProfile['about'] = strip_tags($userProfile['about'], '');
        $userProfile['about'] = preg_replace('/ /', '', $userProfile['about']);
        $user = array_merge($user, $userProfile);
        $classrooms = array();

        $studentClassrooms = $this->getClassroomService()->searchMembers(array('role' => 'student', 'userId' => $user['id']), array('createdTime' => 'desc'), 0, PHP_INT_MAX);
        $auditorClassrooms = $this->getClassroomService()->searchMembers(array('role' => 'auditor', 'userId' => $user['id']), array('createdTime' => 'desc'), 0, PHP_INT_MAX);

        $classrooms = array_merge($studentClassrooms, $auditorClassrooms);

        $classroomIds = ArrayToolkit::column($classrooms, 'classroomId');

        if (!empty($classroomIds)) {
            $conditions = array(
                'status' => 'published',
                'showable' => '1',
                'classroomIds' => $classroomIds,
            );

            $paginator = new Paginator(
                $this->get('request'),
                $this->getClassroomService()->countClassrooms($conditions),
                20
            );

            $classrooms = $this->getClassroomService()->searchClassrooms(
                $conditions,
                array('createdTime' => 'DESC'),
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );

            foreach ($classrooms as $key => $classroom) {
                if (empty($classroom['teacherIds'])) {
                    $classroomTeacherIds = array();
                } else {
                    $classroomTeacherIds = $classroom['teacherIds'];
                }

                $teachers = $this->getUserService()->findUsersByIds($classroomTeacherIds);
                $classrooms[$key]['teachers'] = $teachers;
            }
        } else {
            $paginator = new Paginator(
                $this->get('request'),
                0,
                20
            );
        }

        return $this->render('user/classroom-learning.html.twig', array(
            'paginator' => $paginator,
            'classrooms' => $classrooms,
            'user' => $user,
        ));
    }

    public function teachingAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $userProfile['about'] = strip_tags($userProfile['about'], '');
        $userProfile['about'] = preg_replace('/ /', '', $userProfile['about']);
        $user = array_merge($user, $userProfile);
        $conditions = array(
            'roles' => array('teacher', 'headTeacher'),
            'userId' => $user['id'],
        );
        $classroomMembers = $this->getClassroomService()->searchMembers($conditions, array('createdTime' => 'desc'), 0, PHP_INT_MAX);

        $classroomIds = ArrayToolkit::column($classroomMembers, 'classroomId');
        if (empty($classroomIds)) {
            $paginator = new Paginator(
                $this->get('request'),
                0,
                20
            );
            $classrooms = array();
        } else {
            $conditions = array(
                'status' => 'published',
                'showable' => '1',
                'classroomIds' => $classroomIds,
            );

            $paginator = new Paginator(
                $this->get('request'),
                $this->getClassroomService()->countClassrooms($conditions),
                20
            );

            $classrooms = $this->getClassroomService()->searchClassrooms(
                $conditions,
                array('createdTime' => 'DESC'),
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );

            foreach ($classrooms as $key => $classroom) {
                if (empty($classroom['teacherIds'])) {
                    $classroomTeacherIds = array();
                } else {
                    $classroomTeacherIds = $classroom['teacherIds'];
                }

                $teachers = $this->getUserService()->findUsersByIds($classroomTeacherIds);
                $classrooms[$key]['teachers'] = $teachers;
            }
        }

        return $this->render('user/classroom-teaching.html.twig', array(
            'paginator' => $paginator,
            'classrooms' => $classrooms,
            'user' => $user,
        ));
    }

   

    public function checkPasswordAction(Request $request)
    {
        $password = $request->query->get('value');
        $currentUser = $this->getCurrentUser();

        if (!$currentUser->isLogin()) {
            $response = array('success' => false, 'message' => '请先登入');
        }

        if (!$this->getUserService()->verifyPassword($currentUser['id'], $password)) {
            $response = array('success' => false, 'message' => '输入的密码不正确');
        } else {
            $response = array('success' => true, 'message' => '');
        }

        return $this->createJsonResponse($response);
    }


    protected function saveUserInfo($request, $user)
    {
        $formData = $request->request->all();

        $userInfo = ArrayToolkit::parts($formData, array(
            'truename',
            'mobile',
            'qq',
            'company',
            'weixin',
            'weibo',
            'idcard',
            'gender',
            'job',
            'intField1', 'intField2', 'intField3', 'intField4', 'intField5',
            'floatField1', 'floatField2', 'floatField3', 'floatField4', 'floatField5',
            'dateField1', 'dateField2', 'dateField3', 'dateField4', 'dateField5',
            'varcharField1', 'varcharField2', 'varcharField3', 'varcharField4', 'varcharField5', 'varcharField10', 'varcharField6', 'varcharField7', 'varcharField8', 'varcharField9',
            'textField1', 'textField2', 'textField3', 'textField4', 'textField5', 'textField6', 'textField7', 'textField8', 'textField9', 'textField10',
        ));

        if (isset($formData['email']) && !empty($formData['email'])) {
            $this->getAuthService()->changeEmail($user['id'], null, $formData['email']);
        }

        $authSetting = $this->setting('auth', array());
        if (!empty($formData['mobile']) && !empty($authSetting['fill_userinfo_after_login']) && !empty($authSetting['mobileSmsValidate'])) {
            $verifiedMobile = $formData['mobile'];
            $this->getUserService()->changeMobile($user['id'], $verifiedMobile);
        }

        $currentUser = new CurrentUser();
        $currentUser->fromArray($this->getUserService()->getUser($user['id']));
        $this->switchUser($request, $currentUser);

        $userInfo = $this->getUserService()->updateUserProfile($user['id'], $userInfo);

        return $userInfo;
    }

    protected function tryGetUser($id)
    {
        $user = $this->getUserService()->getUser($id);

        if (empty($user)) {
            throw $this->createNotFoundException();
        }

        return $user;
    }


    protected function _learnAction($user)
    {
        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseSetService()->countUserLearnCourseSets($user['id']),
            20
        );

        $courseSets = $this->getCourseSetService()->searchUserLearnCourseSets(
            $user['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('user/course-sets.html.twig', array(
            'user' => $user,
            'courseSets' => $courseSets,
            'paginator' => $paginator,
            'type' => 'learn',
        ));
    }

    protected function _teachAction($user)
    {
        $conditions = array(
            'status' => 'published',
            'parentId' => 0,
        );
        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseSetService()->countUserTeachingCourseSets($user['id'], $conditions),
            20
        );

        $sets = $this->getCourseSetService()->searchUserTeachingCourseSets(
            $user['id'],
            $conditions,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('user/course-sets.html.twig', array(
            'user' => $user,
            'courseSets' => $sets,
            'paginator' => $paginator,
            'type' => 'teach',
        ));
    }


    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->getBiz()->service('User:UserService');
    }

    /**
     * @return GroupService
     */
    protected function getGroupService()
    {
        return $this->getBiz()->service('Group:GroupService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->getBiz()->service('Classroom:ClassroomService');
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
     * @return AuthService
     */
    protected function getAuthService()
    {
        return $this->getBiz()->service('User:AuthService');
    }

    /**
     * @return LevelService
     */
    protected function getLevelService()
    {
        return $this->getBiz()->service('VipPlugin:Vip:LevelService');
    }

    /**
     * @return VipService
     */
    protected function getVipService()
    {
        return $this->getBiz()->service('VipPlugin:Vip:VipService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->getBiz()->service('Course:MemberService');
    }

    /**
     * @return ThreadService
     */
    protected function getThreadService()
    {
        return $this->getBiz()->service('Course:ThreadService');
    }

    /**
     * @return CourseNoteService
     */
    protected function getNoteService()
    {
        return $this->getBiz()->service('Course:CourseNoteService');
    }

    /**
     * @return NotificationService
     */
    protected function getNotificationService()
    {
        return $this->getBiz()->service('User:NotificationService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->getBiz()->service('Course:CourseSetService');
    }

    /**
     * @return AccountService
     */
    protected function getAccountService()
    {
        return $this->getBiz()->service('RewardPoint:AccountService');
    }
}
